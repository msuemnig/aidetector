<?php

declare(strict_types=1);

namespace App\Services\AiDetector\Detectors;

use App\Services\AiDetector\DetectorInterface;
use App\Services\AiDetector\DTOs\DetectorResult;

/**
 * Detects formal adverbial sentence openers used as discourse markers.
 *
 * AI opens sentences with commentary adverbs ("Notably,", "Importantly,",
 * "Crucially,") at ~25% rate vs ~5% in human text. These are different from
 * connective transitions (Furthermore, Additionally) — they assign editorial
 * weight to the sentence that follows.
 *
 * Awards 2 points per occurrence, capped at 10.
 */
final class AdverbialOpenerDetector implements DetectorInterface
{
    private const int CAP                   = 10;
    private const int POINTS_PER_OCCURRENCE = 2;

    /**
     * Matches commentary adverbs at sentence start followed by a comma.
     * Uses lookbehind for sentence boundary (start of string, or .!? + whitespace).
     */
    private const string PATTERN = '/(?:^|[.!?]\s+)(Importantly|More importantly|Notably|Significantly|Crucially|Remarkably|Undeniably|Admittedly|Intriguingly|Compellingly|Strikingly|Critically|Fundamentally|Essentially),/m';

    public function analyse(string $text): DetectorResult
    {
        $matches     = [];
        $occurrences = 0;

        $found = [];
        preg_match_all(self::PATTERN, $text, $found, PREG_SET_ORDER);

        foreach ($found as $match) {
            $occurrences++;
            $word = trim($match[1] ?? $match[0], ' ,.');
            $matches[] = $word;
        }

        // DEPRECATED: 0% human vs 2% AI on 136 fixtures — too rare to matter.
        // Score zeroed; kept in pipeline for display.
        $score = 0;

        $explanation = $occurrences === 0
            ? 'No adverbial sentence openers were detected.'
            : sprintf(
                '%d adverbial %s detected (%s). AI uses commentary adverbs as sentence openers at ~25%% rate vs ~5%% human.',
                $occurrences,
                $occurrences === 1 ? 'opener was' : 'openers were',
                implode(', ', array_map(fn($m) => '"' . $m . ',"', array_unique($matches))),
            );

        return new DetectorResult(
            category:    'Adverbial Openers',
            score:       $score,
            cap:         self::CAP,
            occurrences: $occurrences,
            matches:     array_values(array_unique($matches)),
            explanation: $explanation,
            confidence:  'MEDIUM',
        );
    }
}
