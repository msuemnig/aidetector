<?php

declare(strict_types=1);

namespace App\Services\AiDetector\Detectors;

use App\Services\AiDetector\DetectorInterface;
use App\Services\AiDetector\DTOs\DetectorResult;

/**
 * Detects vague "from X to Y" range constructions that lack concrete units or numbers.
 *
 * A range is flagged when neither side contains a digit or a known unit word,
 * indicating that the author is implying scale without providing real data.
 *
 * Awards 3 points per occurrence, capped at 8 points.
 */
final class FalseRangeDetector implements DetectorInterface
{
    private const int CAP                   = 8;
    private const int POINTS_PER_OCCURRENCE = 3;

    /**
     * Matches "from [X] to [Y]" where X and Y are 1-5 words each.
     */
    private const string RANGE_PATTERN = '/\bfrom\s+((?:\w+\s+){0,4}\w+)\s+to\s+((?:\w+\s+){0,4}\w+)/i';

    /**
     * Unit words that indicate a concrete, measurable range.
     *
     * @var list<string>
     */
    private const array UNIT_WORDS = [
        'seconds', 'minutes', 'hours', 'days', 'weeks', 'months', 'years',
        'km', 'miles', 'kg', 'lbs', 'percent', 'dollars', 'pounds', 'euros',
        'metres', 'meters', 'feet', 'inches',
    ];

    /**
     * Analyse the provided text for vague range constructions.
     */
    public function analyse(string $text): DetectorResult
    {
        $rawMatches = [];
        preg_match_all(self::RANGE_PATTERN, $text, $rawMatches, PREG_SET_ORDER);

        $matchedStrings = [];
        $occurrences    = 0;

        foreach ($rawMatches as $match) {
            $fullMatch = trim($match[0]);
            $sideX     = trim($match[1]);
            $sideY     = trim($match[2]);

            if (!$this->sideHasConcreteValue($sideX) && !$this->sideHasConcreteValue($sideY)) {
                $matchedStrings[] = '"' . $fullMatch . '"';
                $occurrences++;
            }
        }

        // DEPRECATED: 15% human vs 20% AI on 136 fixtures (+5pp — too weak).
        // "from X to Y" catches natural expressions like "from one face to another."
        // Score zeroed; kept in pipeline for display.
        $score = 0;

        $explanation = $occurrences === 0
            ? 'No vague range constructions were detected in the text.'
            : sprintf(
                '%d vague "from X to Y" %s detected (no digits or unit words on either side), '
                . 'contributing %d point%s (cap: %d). Matched: %s.',
                $occurrences,
                $occurrences === 1 ? 'construction was' : 'constructions were',
                $score,
                $score === 1 ? '' : 's',
                self::CAP,
                implode(', ', $matchedStrings),
            );

        return new DetectorResult(
            category: 'False Range',
            score: $score,
            cap: self::CAP,
            occurrences: $occurrences,
            matches: $matchedStrings,
            explanation: $explanation,
            confidence: 'LOW',
        );
    }

    /**
     * Determine whether a range side contains a digit or a known unit word.
     */
    private function sideHasConcreteValue(string $side): bool
    {
        // Check for any digit character.
        if (preg_match('/\d/', $side)) {
            return true;
        }

        // Check for a percentage sign.
        if (str_contains($side, '%')) {
            return true;
        }

        // Check for known unit words (case-insensitive, whole-word).
        $lowerSide = mb_strtolower($side);
        foreach (self::UNIT_WORDS as $unit) {
            if (preg_match('/\b' . preg_quote($unit, '/') . '\b/i', $lowerSide)) {
                return true;
            }
        }

        return false;
    }
}
