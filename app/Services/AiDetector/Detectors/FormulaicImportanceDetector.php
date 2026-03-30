<?php

declare(strict_types=1);

namespace App\Services\AiDetector\Detectors;

use App\Services\AiDetector\DetectorInterface;
use App\Services\AiDetector\DTOs\DetectorResult;

/**
 * Detects formulaic importance-assignment phrases characteristic of AI text.
 *
 * These phrases appear 50x-269x more frequently in AI-generated text than human
 * text (GPTZero 3.3M corpus analysis). They represent AI's tendency to assign
 * grandiose significance using rigid constructions.
 *
 * Awards 3 points per distinct phrase, capped at 12.
 */
final class FormulaicImportanceDetector implements DetectorInterface
{
    private const int CAP              = 12;
    private const int POINTS_PER_MATCH = 3;

    private const array PATTERNS = [
        // "plays a [adjective] role" — 50x+ AI frequency
        'plays a crucial/vital/pivotal role' => '/\bplays?\s+a\s+(crucial|vital|pivotal|key|significant|important|central|critical)\s+role\b/i',
        // Copula avoidance — AI substitutes dramatic verbs for "is"
        'serves as a'       => '/\bserves?\s+as\s+a\b/i',
        'stands as a'       => '/\bstands?\s+as\s+a\b/i',
        'has emerged as'    => '/\bhas\s+emerged\s+as\b/i',
        // Significance-assignment — 12%+ AI frequency
        'a testament to'    => '/\ba\s+testament\s+to\b/i',
        'a cornerstone of'  => '/\ba\s+cornerstone\s+of\b/i',
        'a beacon of'       => '/\ba\s+beacon\s+of\b/i',
        // Grandiose framing
        'cannot be overstated'  => '/\bcannot\s+be\s+overstated\b/i',
        'indelible mark'        => '/\bindelible\s+mark\b/i',
        'unwavering commitment' => '/\bunwavering\s+(commitment|dedication|resolve)\b/i',
        'profound impact'       => '/\bprofound\s+(impact|effect|influence)\b/i',
        'deeply rooted'         => '/\bdeeply\s+rooted\b/i',
    ];

    public function analyse(string $text): DetectorResult
    {
        $matches     = [];
        $occurrences = 0;

        foreach (self::PATTERNS as $label => $pattern) {
            if (preg_match($pattern, $text)) {
                $occurrences++;
                $matches[] = $label;
            }
        }

        $score = min($occurrences * self::POINTS_PER_MATCH, self::CAP);

        $explanation = $occurrences === 0
            ? 'No formulaic importance phrases were detected.'
            : sprintf(
                '%d formulaic importance %s detected (%s). These appear 50-269x more in AI text than human text (GPTZero 3.3M corpus).',
                $occurrences,
                $occurrences === 1 ? 'phrase was' : 'phrases were',
                implode(', ', array_map(fn($m) => '"' . $m . '"', array_slice($matches, 0, 5))),
            );

        return new DetectorResult(
            category:    'Formulaic Importance',
            score:       $score,
            cap:         self::CAP,
            occurrences: $occurrences,
            matches:     $matches,
            explanation: $explanation,
            confidence:  'HIGH',
        );
    }
}
