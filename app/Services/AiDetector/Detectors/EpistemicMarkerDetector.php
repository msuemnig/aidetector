<?php

declare(strict_types=1);

namespace App\Services\AiDetector\Detectors;

use App\Services\AiDetector\DetectorInterface;
use App\Services\AiDetector\DTOs\DetectorResult;

/**
 * Detects epistemic markers — phrases expressing uncertainty, personal belief,
 * or subjective qualification that are characteristic of human writing.
 *
 * GPT-4 uses epistemic markers at a rate of essentially 0.00 per essay;
 * human writers average 0.06 per essay (Herbold et al., Nature Scientific Reports, 2023).
 *
 * This is a NEGATIVE-SCORE detector: presence of markers reduces the AI score.
 * Score is negative (−3 to −8) when markers are found.
 *
 * Citation: Herbold et al. (2023), Nature Scientific Reports — largest effect size
 *           in the study. PNAS 2023: Reinhart et al. on LLM rhetorical styles.
 */
final class EpistemicMarkerDetector implements DetectorInterface
{
    private const int CAP = -8;  // Maximum negative contribution

    /**
     * Phrases grouped by type. Case-insensitive matching.
     */
    private const array MARKERS = [
        // Uncertainty hedges
        'i think', 'i believe', 'i feel', 'perhaps', 'maybe', 'probably',
        'possibly', 'it seems', 'it appears', 'might be', 'could be',
        'i suppose', 'i reckon', 'i guess', 'arguably',
        // Subjective qualifiers
        'in my opinion', 'in my experience', 'from my perspective',
        'as far as i can tell', 'to my knowledge', 'as i see it', 'i would say',
        'from what i', 'to me,',
        // Self-correction / discourse hedging
        'well, actually', 'or rather', 'that said', 'then again',
        'on second thought', 'i mean,',
    ];

    public function analyse(string $text): DetectorResult
    {
        $lower       = mb_strtolower($text);
        $matches     = [];
        $occurrences = 0;

        foreach (self::MARKERS as $phrase) {
            $count = substr_count($lower, $phrase);
            if ($count > 0) {
                $occurrences += $count;
                $matches[] = $phrase;
            }
        }

        // Negative score: more markers = more negative (human signal)
        $score = $occurrences > 0 ? max(self::CAP, -2 * $occurrences) : 0;

        $explanation = $occurrences === 0
            ? 'No epistemic markers (hedging, uncertainty, personal belief) were found. AI text typically lacks these markers entirely.'
            : sprintf(
                '%d epistemic %s detected (%s). These markers express uncertainty or personal perspective — a strong indicator of human writing (Herbold et al., 2023).',
                $occurrences,
                $occurrences === 1 ? 'marker was' : 'markers were',
                implode(', ', array_map(fn($m) => '"' . $m . '"', array_slice($matches, 0, 5))),
            );

        return new DetectorResult(
            category:    'Epistemic Markers',
            score:       $score,
            cap:         self::CAP,
            occurrences: $occurrences,
            matches:     $matches,
            explanation: $explanation,
            confidence:  'HIGH',
        );
    }
}
