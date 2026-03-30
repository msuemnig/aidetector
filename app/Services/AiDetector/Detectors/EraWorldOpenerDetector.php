<?php

declare(strict_types=1);

namespace App\Services\AiDetector\Detectors;

use App\Services\AiDetector\DetectorInterface;
use App\Services\AiDetector\DTOs\DetectorResult;

/**
 * Detects cliché temporal/contextual opening formulas characteristic of AI text.
 *
 * "In an era of...", "In a world where...", "In the ever-evolving landscape..."
 * — AI uses these as generic hooks. GPTZero found "today's digital age" in their
 * top-50 AI phrases. Wikipedia's Signs of AI Writing identifies these broad
 * contextual openers as a key AI tell.
 *
 * Awards 4 points per occurrence, capped at 10.
 *
 * Citation: GPTZero AI Vocabulary; Wikipedia Signs of AI Writing; Pangram Labs.
 */
final class EraWorldOpenerDetector implements DetectorInterface
{
    private const int CAP                   = 10;
    private const int POINTS_PER_OCCURRENCE = 4;

    private const array PATTERNS = [
        // "In an era of/where"
        'in an era'          => '/\bIn an era\s+(of|where)\b/i',
        // "In a world where/of"
        'in a world'         => '/\bIn a world\s+(where|of)\b/i',
        // "In the [adjective] world/landscape/realm of"
        'in the X world of'  => '/\bIn the\s+(?:ever-?|rapidly |fast-paced |dynamic )?(?:evolving|changing|shifting|competitive)\s+(?:world|landscape|realm)\b/i',
        // "In today's [adjective] [noun]" (beyond the specific phrases already in AI Vocabulary)
        'in today\'s X'      => '/\bIn today\'?s\s+\w+\s+(world|age|landscape|era|society|environment|climate)\b/i',
        // "As we navigate/enter/move into"
        'as we navigate'     => '/\bAs we\s+(navigate|enter|move into|step into|embrace)\b/i',
        // "As the world becomes/grows"
        'as the world'       => '/\bAs the world\s+(becomes|grows|continues)\b/i',
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
                $matches[] = $label;
            }
        }

        // DEPRECATED: 0% human vs 0% AI on 136 fixtures — never fires on
        // topic-constrained text. May work on generic blog posts but not
        // academic/creative/opinion writing. Score zeroed.
        $score = 0;

        $explanation = $occurrences === 0
            ? 'No cliché era/world opening formulas were detected.'
            : sprintf(
                '%d cliché opening %s detected (%s). GPTZero identifies these as top-50 AI phrases.',
                $occurrences,
                $occurrences === 1 ? 'formula was' : 'formulas were',
                implode(', ', array_map(fn($m) => '"' . $m . '"', $matches)),
            );

        return new DetectorResult(
            category:    'Era/World Openers',
            score:       $score,
            cap:         self::CAP,
            occurrences: $occurrences,
            matches:     $matches,
            explanation: $explanation,
            confidence:  'MEDIUM',
        );
    }
}
