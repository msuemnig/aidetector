<?php

declare(strict_types=1);

namespace App\Services\AiDetector\Detectors;

use App\Services\AiDetector\DetectorInterface;
use App\Services\AiDetector\DTOs\DetectorResult;

/**
 * Detects trailing participle clauses that AI uses to append shallow editorial commentary.
 *
 * Pattern: ", [verb]-ing [the/a/its/their] ..." at the end of a clause.
 * Example: "The city opened a new park, highlighting its commitment to green spaces."
 *
 * AI uses these at 2-5x the rate of human writers (Wikipedia Signs of AI Writing,
 * stryng.io, tropes.fyi).
 *
 * Awards 3 points per occurrence, capped at 10.
 */
final class TrailingParticipleDetector implements DetectorInterface
{
    private const int CAP                   = 10;
    private const int POINTS_PER_OCCURRENCE = 3;

    /**
     * Participial verbs that AI appends as editorial commentary.
     * These are specifically the "significance-claiming" participles,
     * not ordinary participial clauses like "running to the store."
     */
    private const string PATTERN = '/,\s+(highlighting|emphasizing|underscoring|reflecting|showcasing|demonstrating|illustrating|signaling|signalling|reinforcing|solidifying|cementing|underscoring|paving the way|marking a|shaping the|transforming the|representing a|indicating|suggesting|revealing|capturing|encapsulating|embodying|exemplifying)\b/i';

    public function analyse(string $text): DetectorResult
    {
        $matches     = [];
        $occurrences = 0;

        $found = [];
        preg_match_all(self::PATTERN, $text, $found, PREG_SET_ORDER);

        foreach ($found as $match) {
            $occurrences++;
            $matches[] = trim($match[0]);
        }

        $score = min($occurrences * self::POINTS_PER_OCCURRENCE, self::CAP);

        $explanation = $occurrences === 0
            ? 'No trailing participle clauses were detected.'
            : sprintf(
                '%d trailing participle %s detected (%s). AI appends editorial "-ing" clauses at 2-5x the human rate (Wikipedia Signs of AI Writing).',
                $occurrences,
                $occurrences === 1 ? 'clause was' : 'clauses were',
                implode(', ', array_map(fn($m) => '"' . $m . '"', array_slice($matches, 0, 4))),
            );

        return new DetectorResult(
            category:    'Trailing Participle',
            score:       $score,
            cap:         self::CAP,
            occurrences: $occurrences,
            matches:     $matches,
            explanation: $explanation,
            confidence:  'HIGH',
        );
    }
}
