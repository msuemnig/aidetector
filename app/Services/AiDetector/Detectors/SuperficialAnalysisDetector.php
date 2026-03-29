<?php

declare(strict_types=1);

namespace App\Services\AiDetector\Detectors;

use App\Services\AiDetector\DetectorInterface;
use App\Services\AiDetector\DTOs\DetectorResult;

/**
 * Detects filler phrases that give the impression of analysis without adding substance.
 *
 * These phrases are common in AI-generated text where the model pads content
 * rather than providing genuine insight.
 *
 * Awards 2 points per occurrence, capped at 10 points.
 */
final class SuperficialAnalysisDetector implements DetectorInterface
{
    private const int CAP                   = 10;
    private const int POINTS_PER_OCCURRENCE = 2;

    /**
     * Phrases indicative of superficial, padded analysis.
     *
     * @var list<string>
     */
    private const array PHRASES = [
        'it is worth noting',
        'significant developments',
        'one could argue',
        'various sources indicate',
        'it should be noted',
        'it is important to consider',
        'as previously mentioned',
        'needless to say',
        'it goes without saying',
    ];

    /**
     * Analyse the provided text for superficial analysis phrases.
     */
    public function analyse(string $text): DetectorResult
    {
        $matchedStrings = [];
        $occurrences    = 0;

        foreach (self::PHRASES as $phrase) {
            $escaped = preg_quote($phrase, '/');
            $pattern = '/\b' . $escaped . '\b/i';

            $found = [];
            preg_match_all($pattern, $text, $found);

            $count = count($found[0]);
            if ($count > 0) {
                $occurrences   += $count;
                // Record each occurrence individually in matches.
                for ($i = 0; $i < $count; $i++) {
                    $matchedStrings[] = $phrase;
                }
            }
        }

        $score = min($occurrences * self::POINTS_PER_OCCURRENCE, self::CAP);

        $explanation = $occurrences === 0
            ? 'No superficial analysis phrases were detected in the text.'
            : sprintf(
                '%d superficial analysis phrase %s detected, contributing %d point%s (cap: %d). '
                . 'Phrases found: %s.',
                $occurrences,
                $occurrences === 1 ? 'occurrence was' : 'occurrences were',
                $score,
                $score === 1 ? '' : 's',
                self::CAP,
                implode(', ', array_map(
                    static fn(string $p): string => '"' . $p . '"',
                    array_unique($matchedStrings),
                )),
            );

        return new DetectorResult(
            category: 'Superficial Analysis',
            score: $score,
            cap: self::CAP,
            occurrences: $occurrences,
            matches: $matchedStrings,
            explanation: $explanation,
            confidence: 'MEDIUM',
        );
    }
}
