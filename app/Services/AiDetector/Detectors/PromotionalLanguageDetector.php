<?php

declare(strict_types=1);

namespace App\Services\AiDetector\Detectors;

use App\Services\AiDetector\DetectorInterface;
use App\Services\AiDetector\DTOs\DetectorResult;

/**
 * Detects promotional marketing language commonly inserted by AI text generators.
 *
 * Such phrases are frequently used when an AI model frames a topic in a
 * sales-oriented or marketing-heavy manner without genuine substance.
 *
 * Awards 2 points per phrase occurrence, capped at 10 points.
 */
final class PromotionalLanguageDetector implements DetectorInterface
{
    private const int CAP                   = 10;
    private const int POINTS_PER_OCCURRENCE = 2;

    /**
     * Promotional phrases to detect (case-insensitive, whole-phrase).
     *
     * @var list<string>
     */
    private const array PHRASES = [
        'game-changer',
        'revolutionary',
        'impressive features',
        'transformative potential',
        'cutting-edge solutions',
        'industry-leading',
        'world-class',
        'best-in-class',
        'next-generation',
        'state-of-the-art',
        'powerful solution',
    ];

    /**
     * Analyse the provided text for promotional language.
     */
    public function analyse(string $text): DetectorResult
    {
        $matchedStrings = [];
        $occurrences    = 0;

        foreach (self::PHRASES as $phrase) {
            $escaped = preg_quote($phrase, '/');

            // Use word boundary where possible; hyphens break \b so we anchor on
            // non-word chars or string edges for hyphenated phrases.
            $pattern = '/(?<!\w)' . $escaped . '(?!\w)/i';

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
            ? 'No promotional language phrases were detected in the text.'
            : sprintf(
                '%d promotional language phrase %s detected, contributing %d point%s (cap: %d). '
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
            category: 'Promotional Language',
            score: $score,
            cap: self::CAP,
            occurrences: $occurrences,
            matches: $matchedStrings,
            explanation: $explanation,
            confidence: 'MEDIUM',
        );
    }
}
