<?php

declare(strict_types=1);

namespace App\Services\AiDetector\Detectors;

use App\Services\AiDetector\DetectorInterface;
use App\Services\AiDetector\DTOs\DetectorResult;

/**
 * Detects contractions — a marker of informal, natural writing.
 *
 * LLMs tend toward expanded forms ("do not", "cannot") especially in formal-ish text.
 * Presence of contractions at natural rates is a human signal.
 *
 * This is a NEGATIVE-SCORE detector: contractions reduce the AI score.
 *
 * Citation: PNAS 2023 (PMC10089155) — contractions validated as a human-perceived signal.
 *           Arxiv 2505.01800 — psycholinguistic analysis of AI vs human text.
 */
final class ContractionDetector implements DetectorInterface
{
    private const int CAP = -6;  // Maximum negative contribution

    /**
     * Common English contractions.
     */
    private const string CONTRACTION_PATTERN = "/\b(?:"
        . "i'm|i've|i'll|i'd|"
        . "you're|you've|you'll|you'd|"
        . "he's|he'll|he'd|she's|she'll|she'd|"
        . "it's|it'll|it'd|"
        . "we're|we've|we'll|we'd|"
        . "they're|they've|they'll|they'd|"
        . "that's|that'll|that'd|"
        . "who's|who'll|who'd|"
        . "what's|what'll|what'd|"
        . "there's|there'll|there'd|"
        . "here's|"
        . "don't|doesn't|didn't|"
        . "won't|wouldn't|"
        . "can't|cannot|couldn't|"
        . "shouldn't|shan't|"
        . "isn't|aren't|wasn't|weren't|"
        . "hasn't|haven't|hadn't|"
        . "mustn't|mightn't|needn't|"
        . "let's|ain't"
        . ")\b/i";

    public function analyse(string $text): DetectorResult
    {
        $allMatches  = [];
        preg_match_all(self::CONTRACTION_PATTERN, $text, $allMatches);

        $found       = $allMatches[0] ?? [];
        $occurrences = count($found);
        $unique      = array_unique(array_map('mb_strtolower', $found));

        // DEPRECATED: Originally a negative-score human signal, but 136-fixture analysis
        // showed contractions fire 53% on AI vs 29% on human. AI fiction/memoir uses
        // contractions freely. Score set to 0 — kept in pipeline for display only.
        $score = 0;

        $wordCount = str_word_count($text);
        $rate      = $wordCount > 0 ? ($occurrences / $wordCount) * 100 : 0.0;

        $explanation = $occurrences === 0
            ? 'No contractions were found. AI text tends to use expanded forms ("do not" instead of "don\'t").'
            : sprintf(
                '%d %s found (%.1f%% of words): %s. Contractions are characteristic of natural human writing (PNAS 2023).',
                $occurrences,
                $occurrences === 1 ? 'contraction' : 'contractions',
                $rate,
                implode(', ', array_map(fn($m) => '"' . $m . '"', array_slice(array_values($unique), 0, 6))),
            );

        return new DetectorResult(
            category:    'Contraction Usage',
            score:       $score,
            cap:         self::CAP,
            occurrences: $occurrences,
            matches:     array_values($unique),
            explanation: $explanation,
            confidence:  'MEDIUM',
        );
    }
}
