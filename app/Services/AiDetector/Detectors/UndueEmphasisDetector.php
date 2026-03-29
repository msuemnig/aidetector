<?php

declare(strict_types=1);

namespace App\Services\AiDetector\Detectors;

use App\Services\AiDetector\DetectorInterface;
use App\Services\AiDetector\DTOs\DetectorResult;

/**
 * Detects undue emphasis through hyperbolic vocabulary, excessive punctuation,
 * and shouting (ALL CAPS) usage.
 *
 * Three signal types are detected:
 *   1. Hyperbolic adjectives/adverbs (e.g. "tremendous", "groundbreaking").
 *   2. Multiple consecutive exclamation marks (!! or more).
 *   3. ALL CAPS words longer than 3 characters that do not appear at a sentence start.
 *
 * Awards 2 points per term/occurrence, capped at 10 points.
 */
final class UndueEmphasisDetector implements DetectorInterface
{
    private const int CAP                   = 10;
    private const int POINTS_PER_OCCURRENCE = 2;

    /**
     * Hyperbolic emphasis terms to detect (case-insensitive, whole-word).
     *
     * @var list<string>
     */
    private const array HYPERBOLIC_TERMS = [
        'tremendous',
        'remarkable',
        'groundbreaking',
        'extraordinary',
        'exceptional',
        'incredible',
        'outstanding',
        'unparalleled',
        'unprecedented',
        'revolutionary',
        'monumental',
        'spectacular',
    ];

    /**
     * Matches two or more consecutive exclamation marks.
     */
    private const string MULTI_EXCLAMATION_PATTERN = '/!{2,}/';

    /**
     * Matches ALL CAPS words of 4 or more characters.
     * The negative lookbehind ensures we do not match at the very start of a sentence
     * (i.e. preceded by a sentence-ending character + optional whitespace, or at position 0).
     */
    private const string ALL_CAPS_PATTERN = '/(?<![.!?]\s{0,5})(?<!\A)\b([A-Z]{4,})\b/';

    /**
     * Analyse the provided text for undue emphasis signals.
     */
    public function analyse(string $text): DetectorResult
    {
        $matchedStrings = [];
        $occurrences    = 0;

        // --- 1. Hyperbolic vocabulary terms ---
        foreach (self::HYPERBOLIC_TERMS as $term) {
            $escaped = preg_quote($term, '/');
            $pattern = '/\b' . $escaped . '\b/i';

            $found = [];
            preg_match_all($pattern, $text, $found);

            $count = count($found[0]);
            if ($count > 0) {
                $occurrences += $count;
                for ($i = 0; $i < $count; $i++) {
                    $matchedStrings[] = $term;
                }
            }
        }

        // --- 2. Multiple consecutive exclamation marks ---
        $exclamationMatches = [];
        preg_match_all(self::MULTI_EXCLAMATION_PATTERN, $text, $exclamationMatches);

        foreach ($exclamationMatches[0] as $match) {
            $matchedStrings[] = 'Excessive exclamation marks: "' . $match . '"';
            $occurrences++;
        }

        // --- 3. ALL CAPS words (4+ chars, not at sentence start) ---
        // We handle sentence-start exclusion by splitting on sentences and checking
        // each word's position within the sentence.
        $allCapsMatches = $this->findAllCapsWords($text);

        foreach ($allCapsMatches as $capsWord) {
            $matchedStrings[] = 'ALL CAPS: "' . $capsWord . '"';
            $occurrences++;
        }

        $score = min($occurrences * self::POINTS_PER_OCCURRENCE, self::CAP);

        $explanation = $occurrences === 0
            ? 'No undue emphasis signals were detected in the text.'
            : sprintf(
                '%d undue emphasis %s detected, contributing %d point%s (cap: %d).',
                $occurrences,
                $occurrences === 1 ? 'signal was' : 'signals were',
                $score,
                $score === 1 ? '' : 's',
                self::CAP,
            );

        return new DetectorResult(
            category: 'Undue Emphasis',
            score: $score,
            cap: self::CAP,
            occurrences: $occurrences,
            matches: $matchedStrings,
            explanation: $explanation,
            confidence: 'MEDIUM',
        );
    }

    /**
     * Find ALL CAPS words (4+ characters) that do not appear at the start of a sentence.
     *
     * A "sentence start" is defined as the first word after a sentence-ending punctuation
     * mark (. ! ?) and optional whitespace, or the very first word in the text.
     *
     * @return list<string>
     */
    private function findAllCapsWords(string $text): array
    {
        $results = [];

        // Split into sentences so we can exclude the first word of each.
        $sentences = preg_split('/(?<=[.!?])\s+/', $text);
        if ($sentences === false) {
            $sentences = [$text];
        }

        foreach ($sentences as $sentence) {
            $sentence = trim($sentence);
            if ($sentence === '') {
                continue;
            }

            // Find all ALL CAPS tokens (4+ chars) in this sentence.
            $allCapsFound = [];
            preg_match_all('/\b([A-Z]{4,})\b/', $sentence, $allCapsFound, PREG_OFFSET_CAPTURE);

            foreach ($allCapsFound[1] as [$word, $offset]) {
                // Exclude the very first word of the sentence (offset 0, possibly with leading
                // punctuation — find the first word-character position).
                $firstWordStart = (int) strspn($sentence, " \t\r\n\"'(");
                if ($offset === $firstWordStart) {
                    // This is the first word of the sentence; skip it.
                    continue;
                }

                $results[] = $word;
            }
        }

        return $results;
    }
}
