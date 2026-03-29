<?php

declare(strict_types=1);

namespace App\Services\AiDetector\Detectors;

use App\Services\AiDetector\DetectorInterface;
use App\Services\AiDetector\DTOs\DetectorResult;

/**
 * Detects the "rule of three" rhetorical pattern commonly used by AI text generators.
 *
 * Identifies comma-separated lists of exactly three parallel items in the form:
 * "[item], [item], and [item]".
 *
 * Awards 3 points per occurrence, capped at 10 points.
 */
final class RuleOfThreeDetector implements DetectorInterface
{
    private const int CAP             = 10;
    private const int POINTS_PER_OCCURRENCE = 3;

    /**
     * Matches: word/phrase, word/phrase, and word/phrase
     * Each item may be up to ~3 words long (word + up to 20 chars of word-chars/spaces).
     */
    private const string PATTERN = '/\b(\w[\w\s]{0,20}?),\s+(\w[\w\s]{0,20}?),\s+and\s+(\w[\w\s]{0,20}?\w)\b/i';

    /**
     * Analyse the provided text for rule-of-three patterns.
     */
    public function analyse(string $text): DetectorResult
    {
        $rawMatches = [];

        preg_match_all(self::PATTERN, $text, $rawMatches, PREG_SET_ORDER);

        $occurrences = count($rawMatches);
        $score       = min($occurrences * self::POINTS_PER_OCCURRENCE, self::CAP);

        // Collect the full matched strings for the matches array.
        $matchedStrings = array_map(
            static fn(array $m): string => trim($m[0]),
            $rawMatches,
        );

        $explanation = $occurrences === 0
            ? 'No rule-of-three patterns were detected in the text.'
            : sprintf(
                '%d rule-of-three %s detected, contributing %d point%s (cap: %d). Matched: %s.',
                $occurrences,
                $occurrences === 1 ? 'pattern was' : 'patterns were',
                $score,
                $score === 1 ? '' : 's',
                self::CAP,
                implode('; ', array_map(
                    static fn(string $s): string => '"' . $s . '"',
                    $matchedStrings,
                )),
            );

        return new DetectorResult(
            category: 'Rule of Three',
            score: $score,
            cap: self::CAP,
            occurrences: $occurrences,
            matches: $matchedStrings,
            explanation: $explanation,
            confidence: 'MEDIUM',
        );
    }
}
