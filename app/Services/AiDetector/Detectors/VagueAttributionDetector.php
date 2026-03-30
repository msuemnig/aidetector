<?php

declare(strict_types=1);

namespace App\Services\AiDetector\Detectors;

use App\Services\AiDetector\DetectorInterface;
use App\Services\AiDetector\DTOs\DetectorResult;

/**
 * Detects vague attribution phrases that cite unnamed experts or studies.
 *
 * Phrases are only flagged when the containing sentence does NOT include:
 *   - A four-digit year (e.g. 2023)
 *   - The phrase "study by"
 *   - "according to [Capital Letter…]" (i.e. a named attribution)
 *
 * Awards 3 points per distinct phrase type found, capped at 12 points.
 */
final class VagueAttributionDetector implements DetectorInterface
{
    private const int CAP                     = 12;
    private const int POINTS_PER_PHRASE_TYPE  = 3;

    /**
     * Vague attribution phrases to detect (case-insensitive, whole-phrase).
     *
     * @var list<string>
     */
    private const array PHRASES = [
        'experts agree',
        'studies show',
        'research indicates',
        'research suggests',
        'industry insiders report',
        'according to experts',
        'many experts believe',
        'some researchers suggest',
        'it has been shown',
        'evidence suggests',
    ];

    /**
     * Patterns that indicate a specific attribution is present in the same sentence,
     * which disqualifies the phrase from being flagged.
     *
     * @var list<string>
     */
    private const array SPECIFIC_ATTRIBUTION_PATTERNS = [
        '/\b\d{4}\b/',                   // Four-digit year
        '/\bstudy\s+by\b/i',             // "study by"
        '/\baccording\s+to\s+[A-Z]/i',   // "according to [Named source]"
    ];

    /**
     * Analyse the provided text for vague attribution phrases.
     */
    public function analyse(string $text): DetectorResult
    {
        // Split text into sentences for per-sentence context checking.
        $sentences = $this->splitIntoSentences($text);

        /** @var array<string, bool> $matchedPhraseTypes Tracks distinct phrase types found. */
        $matchedPhraseTypes = [];
        $matchedStrings     = [];

        foreach (self::PHRASES as $phrase) {
            $escapedPhrase = preg_quote($phrase, '/');
            $pattern       = '/\b' . $escapedPhrase . '\b/i';

            foreach ($sentences as $sentence) {
                if (!preg_match($pattern, $sentence)) {
                    continue;
                }

                // Skip if the sentence contains a specific attribution.
                if ($this->hasSpecificAttribution($sentence)) {
                    continue;
                }

                // Record this phrase type as matched (distinct tracking).
                if (!isset($matchedPhraseTypes[$phrase])) {
                    $matchedPhraseTypes[$phrase] = true;
                    $matchedStrings[]            = $phrase;
                }
            }
        }

        $distinctCount = count($matchedPhraseTypes);
        // DEPRECATED: 3% human vs 0% AI on 136 fixtures — fires on human only.
        // Score zeroed; kept in pipeline for display.
        $score = 0;

        $explanation = $distinctCount === 0
            ? 'No unattributed vague attribution phrases were detected in the text.'
            : sprintf(
                '%d distinct vague attribution phrase %s detected (%s), '
                . 'contributing %d point%s (cap: %d).',
                $distinctCount,
                $distinctCount === 1 ? 'type was' : 'types were',
                implode(', ', array_map(
                    static fn(string $p): string => '"' . $p . '"',
                    $matchedStrings,
                )),
                $score,
                $score === 1 ? '' : 's',
                self::CAP,
            );

        return new DetectorResult(
            category: 'Vague Attribution',
            score: $score,
            cap: self::CAP,
            occurrences: $distinctCount,
            matches: $matchedStrings,
            explanation: $explanation,
            confidence: 'MEDIUM',
        );
    }

    /**
     * Split text into individual sentences using punctuation boundaries.
     *
     * @return list<string>
     */
    private function splitIntoSentences(string $text): array
    {
        // Split on sentence-ending punctuation followed by whitespace or end-of-string.
        $parts = preg_split('/(?<=[.!?])\s+/', trim($text));

        return array_values(array_filter(
            $parts !== false ? $parts : [$text],
            static fn(string $s): bool => $s !== '',
        ));
    }

    /**
     * Determine whether a sentence contains a specific, named attribution.
     */
    private function hasSpecificAttribution(string $sentence): bool
    {
        foreach (self::SPECIFIC_ATTRIBUTION_PATTERNS as $pattern) {
            if (preg_match($pattern, $sentence)) {
                return true;
            }
        }

        return false;
    }
}
