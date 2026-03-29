<?php

declare(strict_types=1);

namespace App\Services\AiDetector\Detectors;

use App\Services\AiDetector\DetectorInterface;
use App\Services\AiDetector\DTOs\DetectorResult;

/**
 * Detects "elegant variation" — the AI tendency to avoid repeating a word by cycling
 * through synonyms, which ironically makes the text feel artificial.
 *
 * A synonym cluster is flagged when 3 or more distinct members from that cluster
 * appear anywhere in the text. Each triggered cluster awards 4 points, capped at 8 points.
 */
final class ElegantVariationDetector implements DetectorInterface
{
    private const int CAP                    = 8;
    private const int POINTS_PER_CLUSTER     = 4;
    private const int CLUSTER_TRIGGER_COUNT  = 3;

    /**
     * Synonym clusters. Keys are human-readable cluster names; values are lists of synonyms.
     *
     * @var array<string, list<string>>
     */
    private const array CLUSTERS = [
        'company' => [
            'company',
            'organisation',
            'firm',
            'enterprise',
            'corporation',
            'entity',
        ],
        'use' => [
            'use',
            'utilise',
            'employ',
            'leverage',
            'apply',
        ],
        'show' => [
            'show',
            'demonstrate',
            'illustrate',
            'highlight',
            'underscore',
            'reveal',
        ],
        'problem' => [
            'problem',
            'issue',
            'challenge',
            'concern',
            'obstacle',
            'difficulty',
        ],
        'help' => [
            'help',
            'assist',
            'support',
            'facilitate',
            'enable',
            'empower',
        ],
    ];

    /**
     * Analyse the provided text for elegant variation across synonym clusters.
     */
    public function analyse(string $text): DetectorResult
    {
        $triggeredClusters = [];
        $occurrences       = 0;

        foreach (self::CLUSTERS as $clusterName => $synonyms) {
            $foundMembers = [];

            foreach ($synonyms as $synonym) {
                $escaped = preg_quote($synonym, '/');
                $pattern = '/\b' . $escaped . '\b/i';

                if (preg_match($pattern, $text)) {
                    $foundMembers[] = $synonym;
                }
            }

            if (count($foundMembers) >= self::CLUSTER_TRIGGER_COUNT) {
                $triggeredClusters[] = $clusterName . ' (' . implode(', ', $foundMembers) . ')';
                $occurrences++;
            }
        }

        $score = min($occurrences * self::POINTS_PER_CLUSTER, self::CAP);

        // matches contains the triggered cluster names only (without member detail).
        $matchNames = array_map(
            static fn(string $entry): string => explode(' ', $entry)[0],
            $triggeredClusters,
        );

        $explanation = $occurrences === 0
            ? 'No synonym clusters with 3 or more distinct members were detected in the text.'
            : sprintf(
                '%d synonym %s triggered (requiring %d+ distinct members each), '
                . 'contributing %d point%s (cap: %d). Clusters: %s.',
                $occurrences,
                $occurrences === 1 ? 'cluster was' : 'clusters were',
                self::CLUSTER_TRIGGER_COUNT,
                $score,
                $score === 1 ? '' : 's',
                self::CAP,
                implode('; ', $triggeredClusters),
            );

        return new DetectorResult(
            category: 'Elegant Variation',
            score: $score,
            cap: self::CAP,
            occurrences: $occurrences,
            matches: $matchNames,
            explanation: $explanation,
            confidence: 'MEDIUM',
        );
    }
}
