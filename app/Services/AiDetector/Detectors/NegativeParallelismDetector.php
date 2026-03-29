<?php

declare(strict_types=1);

namespace App\Services\AiDetector\Detectors;

use App\Services\AiDetector\DetectorInterface;
use App\Services\AiDetector\DTOs\DetectorResult;

/**
 * Detects formal parallel constructions characteristic of AI-generated prose.
 *
 * Detects the following patterns:
 *   - "not only … but also"
 *   - "neither … nor"
 *   - "both … and" (formal parallel usage)
 *
 * Awards 4 points per occurrence, capped at 10 points.
 */
final class NegativeParallelismDetector implements DetectorInterface
{
    private const int CAP                   = 10;
    private const int POINTS_PER_OCCURRENCE = 4;

    /**
     * Named patterns for each formal parallel construction.
     *
     * @var array<string, string>
     */
    private const array PATTERNS = [
        'not only … but also' => '/\bnot\s+only\b.{1,120}?\bbut\s+also\b/is',
        'neither … nor'       => '/\bneither\b.{1,120}?\bnor\b/is',
        'both … and'          => '/\bboth\b.{1,120}?\band\b/is',
    ];

    /**
     * Analyse the provided text for formal parallel constructions.
     */
    public function analyse(string $text): DetectorResult
    {
        $matchedStrings = [];
        $occurrences    = 0;

        foreach (self::PATTERNS as $label => $pattern) {
            $found = [];
            preg_match_all($pattern, $text, $found, PREG_SET_ORDER);

            foreach ($found as $match) {
                $matchedStrings[] = $label . ': "' . trim($match[0]) . '"';
                $occurrences++;
            }
        }

        $score = min($occurrences * self::POINTS_PER_OCCURRENCE, self::CAP);

        $explanation = $occurrences === 0
            ? 'No formal parallel constructions were detected in the text.'
            : sprintf(
                '%d formal parallel %s detected, contributing %d point%s (cap: %d).',
                $occurrences,
                $occurrences === 1 ? 'construction was' : 'constructions were',
                $score,
                $score === 1 ? '' : 's',
                self::CAP,
            );

        return new DetectorResult(
            category: 'Negative Parallelism',
            score: $score,
            cap: self::CAP,
            occurrences: $occurrences,
            matches: $matchedStrings,
            explanation: $explanation,
            confidence: 'MEDIUM',
        );
    }
}
