<?php

declare(strict_types=1);

namespace App\Services\AiDetector\Detectors;

use App\Services\AiDetector\DetectorInterface;
use App\Services\AiDetector\DTOs\DetectorResult;

/**
 * Detects vocabulary commonly overused by AI language models.
 *
 * Awards 2 points per distinct matched term, capped at 15 points.
 */
final class AiVocabularyDetector implements DetectorInterface
{
    private const int CAP = 15;
    private const int POINTS_PER_TERM = 2;

    /**
     * Terms and phrases that are characteristic of AI-generated text.
     * Ordered longest-first so multi-word phrases are matched before substrings.
     */
    /**
     * Pruned after 136-fixture analysis. Removed terms that fire equally on
     * human and AI text (legitimate academic vocabulary):
     *   navigate, robust, nuanced, stakeholders, ecosystem, transformative,
     *   comprehensive, underscore, foster, in conclusion, to summarise
     */
    private const array TERMS = [
        // Multi-word phrases — strongest signals, rarely appear in human text
        'actionable insights',
        'innovative solutions',
        'cutting-edge',
        'game-changer',
        'deep dive',
        'delve into',
        'at the end of the day',
        'in today\'s digital landscape',
        'in today\'s world',
        'it is important to note',
        'it is worth noting',
        'best practices',
        // Single words — distinctly AI-favoured, rare in natural writing
        'multifaceted',
        'holistic',
        'paradigm',
        'leverage',
        'streamline',
        'synergy',
        'pivotal',
        'scalable',
    ];

    /**
     * Analyse the provided text for AI vocabulary usage.
     */
    public function analyse(string $text): DetectorResult
    {
        $matches = [];

        foreach (self::TERMS as $term) {
            // Build a whole-word/whole-phrase pattern; escape special regex chars.
            $escaped = preg_quote($term, '/');

            // Use word boundaries; for phrases starting/ending with a word char this works correctly.
            $pattern = '/\b' . $escaped . '\b/iu';

            if (preg_match($pattern, $text)) {
                $matches[] = $term;
            }
        }

        $distinctCount = count($matches);
        $score         = min($distinctCount * self::POINTS_PER_TERM, self::CAP);

        $explanation = $distinctCount === 0
            ? 'No AI vocabulary terms were detected in the text.'
            : sprintf(
                '%d distinct AI vocabulary %s detected (%s), contributing %d point%s (cap: %d).',
                $distinctCount,
                $distinctCount === 1 ? 'term was' : 'terms were',
                implode(', ', array_map(
                    static fn(string $t): string => '"' . $t . '"',
                    $matches,
                )),
                $score,
                $score === 1 ? '' : 's',
                self::CAP,
            );

        return new DetectorResult(
            category: 'AI Vocabulary',
            score: $score,
            cap: self::CAP,
            occurrences: $distinctCount,
            matches: $matches,
            explanation: $explanation,
            confidence: 'HIGH',
        );
    }
}
