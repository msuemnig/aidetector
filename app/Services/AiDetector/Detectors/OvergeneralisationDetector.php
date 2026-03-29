<?php

declare(strict_types=1);

namespace App\Services\AiDetector\Detectors;

use App\Services\AiDetector\DetectorInterface;
use App\Services\AiDetector\DTOs\DetectorResult;

/**
 * Detects overgeneralisation phrases that assert universal truths without evidence.
 *
 * Such phrases are a hallmark of AI text that conflates broad consensus with
 * unsubstantiated claims, lending false authority to statements.
 *
 * Awards 3 points per occurrence, capped at 10 points.
 */
final class OvergeneralisationDetector implements DetectorInterface
{
    private const int CAP                   = 10;
    private const int POINTS_PER_OCCURRENCE = 3;

    /**
     * Phrases that assert unfounded universal consensus or agreement.
     *
     * @var list<string>
     */
    private const array PHRASES = [
        'everyone knows',
        'it is well established',
        'universal consensus',
        'widely accepted',
        'it is commonly understood',
        'it is generally agreed',
        'most people would agree',
        'without a doubt',
        'undeniably',
    ];

    /**
     * Analyse the provided text for overgeneralisation phrases.
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
                $occurrences += $count;
                for ($i = 0; $i < $count; $i++) {
                    $matchedStrings[] = $phrase;
                }
            }
        }

        $score = min($occurrences * self::POINTS_PER_OCCURRENCE, self::CAP);

        $explanation = $occurrences === 0
            ? 'No overgeneralisation phrases were detected in the text.'
            : sprintf(
                '%d overgeneralisation phrase %s detected, contributing %d point%s (cap: %d). '
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
            category: 'Overgeneralisation',
            score: $score,
            cap: self::CAP,
            occurrences: $occurrences,
            matches: $matchedStrings,
            explanation: $explanation,
            confidence: 'HIGH',
        );
    }
}
