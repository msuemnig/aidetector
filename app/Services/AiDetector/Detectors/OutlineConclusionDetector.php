<?php

declare(strict_types=1);

namespace App\Services\AiDetector\Detectors;

use App\Services\AiDetector\DetectorInterface;
use App\Services\AiDetector\DTOs\DetectorResult;

/**
 * Detects formulaic outline and conclusion patterns typical of AI-generated text.
 *
 * Detects:
 *   1. "Despite [phrase], [subject] [verb] [benefit/opportunity phrase]" constructions.
 *   2. Closing sentences beginning with: "In conclusion,", "To summarise,",
 *      "Ultimately,", or "In summary,".
 *
 * Awards 5 points per occurrence, capped at 10 points.
 */
final class OutlineConclusionDetector implements DetectorInterface
{
    private const int CAP                   = 10;
    private const int POINTS_PER_OCCURRENCE = 5;

    /**
     * Pattern for "Despite [phrase], [clause]" constructions.
     * Allows up to 60 characters between "Despite" and the comma.
     */
    private const string DESPITE_PATTERN = '/\bDespite\s+.{3,60}?,\s+\w/i';

    /**
     * Closing sentence opener patterns.
     */
    private const string CLOSING_PATTERN = '/(?:^|[.!?]\s+)(In conclusion|To summarise|Ultimately|In summary)\s*,/im';

    /**
     * Analyse the provided text for outline and conclusion patterns.
     */
    public function analyse(string $text): DetectorResult
    {
        $matchedStrings = [];
        $occurrences    = 0;

        // Check "Despite …, …" patterns.
        $despiteMatches = [];
        preg_match_all(self::DESPITE_PATTERN, $text, $despiteMatches, PREG_SET_ORDER);

        foreach ($despiteMatches as $match) {
            $matchedStrings[] = 'Despite-construction: "' . trim($match[0]) . '…"';
            $occurrences++;
        }

        // Check closing sentence openers.
        $closingMatches = [];
        preg_match_all(self::CLOSING_PATTERN, $text, $closingMatches, PREG_SET_ORDER);

        foreach ($closingMatches as $match) {
            $matchedStrings[] = 'Closing opener: "' . trim($match[0]) . '"';
            $occurrences++;
        }

        $score = min($occurrences * self::POINTS_PER_OCCURRENCE, self::CAP);

        $explanation = $occurrences === 0
            ? 'No formulaic outline or conclusion patterns were detected in the text.'
            : sprintf(
                '%d outline/conclusion %s detected, contributing %d point%s (cap: %d).',
                $occurrences,
                $occurrences === 1 ? 'pattern was' : 'patterns were',
                $score,
                $score === 1 ? '' : 's',
                self::CAP,
            );

        return new DetectorResult(
            category: 'Outline & Conclusion Patterns',
            score: $score,
            cap: self::CAP,
            occurrences: $occurrences,
            matches: $matchedStrings,
            explanation: $explanation,
            confidence: 'MEDIUM',
        );
    }
}
