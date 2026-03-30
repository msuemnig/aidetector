<?php

declare(strict_types=1);

namespace App\Services\AiDetector\Detectors;

use App\Services\AiDetector\DetectorInterface;
use App\Services\AiDetector\DTOs\DetectorResult;

/**
 * Detects the "It's not X — it's Y" dramatic reframe construction.
 *
 * Tropes.fyi calls this "the single most commonly identified AI writing tell."
 * AI uses this construction to create false profundity by framing everything
 * as a surprising reframe. Humans use it occasionally; AI uses it repeatedly.
 *
 * Patterns:
 *   - "It's not [just] X — it's Y"
 *   - "This isn't [just/merely] about X — it's about Y"
 *   - "X is not [just/merely] Y; it's Z"
 *   - "more than just X"
 *
 * Awards 4 points per occurrence, capped at 10.
 *
 * Citation: tropes.fyi AI Writing Pattern Directory
 */
final class DramaticReframeDetector implements DetectorInterface
{
    private const int CAP                   = 10;
    private const int POINTS_PER_OCCURRENCE = 4;

    private const array PATTERNS = [
        // "It's not [just] X — it's Y"
        'it\'s not X — it\'s Y' => '/\bit(?:\'s| is) not (?:just |merely |simply |only )?(?:about |a |an |the )?\w[\w\s]*?[—\-–;]\s*it(?:\'s| is)\b/i',
        // "This isn't [just] about X"
        'this isn\'t about X'   => '/\bthis isn(?:\'t| not) (?:just |merely |simply )?(?:about |a |an )?\w[\w\s]*?[—\-–;]/i',
        // "more than just a/an X"
        'more than just'        => '/\bmore than (?:just |merely |simply )?(?:a |an )?\w+\b/i',
    ];

    public function analyse(string $text): DetectorResult
    {
        $matches     = [];
        $occurrences = 0;

        foreach (self::PATTERNS as $label => $pattern) {
            $found = [];
            $count = preg_match_all($pattern, $text, $found);
            if ($count > 0) {
                $occurrences += $count;
                $matches[] = $label . ' (×' . $count . ')';
            }
        }

        // DEPRECATED: 18% human vs 4% AI on 136 fixtures — fires MORE on human.
        // "more than just" is common natural English. Score zeroed.
        $score = 0;

        $explanation = $occurrences === 0
            ? 'No dramatic reframe constructions were detected.'
            : sprintf(
                '%d dramatic reframe %s detected (%s). Tropes.fyi identifies this as "the single most commonly identified AI writing tell."',
                $occurrences,
                $occurrences === 1 ? 'construction was' : 'constructions were',
                implode(', ', $matches),
            );

        return new DetectorResult(
            category:    'Dramatic Reframe',
            score:       $score,
            cap:         self::CAP,
            occurrences: $occurrences,
            matches:     $matches,
            explanation: $explanation,
            confidence:  'MEDIUM',
        );
    }
}
