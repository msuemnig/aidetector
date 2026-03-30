<?php

declare(strict_types=1);

namespace App\Services\AiDetector\Detectors;

use App\Services\AiDetector\DetectorInterface;
use App\Services\AiDetector\DTOs\DetectorResult;

/**
 * Detects grandiose metaphorical nouns that AI uses to elevate mundane descriptions.
 *
 * Words like "tapestry", "landscape" (abstract), "realm", "beacon" are used
 * metaphorically by AI at 35-50% higher rates than pre-ChatGPT baselines
 * (Max Planck 2024, tropes.fyi).
 *
 * Only matches these nouns in their metaphorical AI context — not literal usage
 * (e.g. "political landscape" is flagged, "mountain landscape" is not).
 *
 * Awards 2 points per distinct match, capped at 8.
 */
final class GrandioseNounDetector implements DetectorInterface
{
    private const int CAP              = 8;
    private const int POINTS_PER_MATCH = 2;

    private const array PATTERNS = [
        // Tapestry/fabric — metaphorical use
        'tapestry (metaphor)'  => '/\b(rich|complex|intricate|diverse|cultural|vibrant)?\s*tapestry\b/i',
        'fabric (metaphor)'    => '/\bthe\s+fabric\s+of\b/i',
        // Landscape — abstract/metaphorical (not geographic)
        'landscape (abstract)' => '/\b(digital|political|educational|economic|social|evolving|ever-changing|shifting|competitive|regulatory|cultural)\s+landscape\b/i',
        // Realm — metaphorical
        'the realm of'         => '/\bthe\s+realm\s+of\b/i',
        // Beacon/cornerstone/catalyst/pillar — metaphorical
        'beacon of'            => '/\ba?\s*beacon\s+of\b/i',
        'catalyst for'         => '/\ba?\s*catalyst\s+for\b/i',
        'pillar of'            => '/\ba?\s*pillar\s+of\b/i',
        // Journey — non-travel metaphorical
        'journey (metaphor)'   => '/\b(embark\w*\s+on\s+a|this|the|our|a\s+transformative|a\s+personal)\s+journey\b/i',
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

        $score = min(count($matches) * self::POINTS_PER_MATCH, self::CAP);

        $explanation = $occurrences === 0
            ? 'No grandiose metaphorical nouns were detected.'
            : sprintf(
                '%d grandiose %s detected (%s). AI uses metaphorical nouns like "tapestry" and "landscape" at 35-50%% elevated rates (Max Planck 2024).',
                $occurrences,
                $occurrences === 1 ? 'noun was' : 'nouns were',
                implode(', ', array_map(fn($m) => '"' . $m . '"', array_slice($matches, 0, 4))),
            );

        return new DetectorResult(
            category:    'Grandiose Nouns',
            score:       $score,
            cap:         self::CAP,
            occurrences: $occurrences,
            matches:     $matches,
            explanation: $explanation,
            confidence:  'MEDIUM',
        );
    }
}
