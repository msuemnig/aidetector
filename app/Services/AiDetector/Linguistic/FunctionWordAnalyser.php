<?php

declare(strict_types=1);

namespace App\Services\AiDetector\Linguistic;

use App\Services\AiDetector\DTOs\LinguisticFactor;

/**
 * Measures the ratio of function words to total words.
 *
 * Function words (articles, prepositions, pronouns, auxiliaries, conjunctions) are
 * the glue of natural language. Humans use more function words; AI-generated text
 * is heavier on content/information words.
 *
 * Thresholds:
 *   > 0.50 → human_signal
 *   0.42–0.50 → normal
 *   < 0.42 → alert (content-word heavy, AI-like)
 *
 * Citation: Arxiv 2407.03646; PLOS One journal.pone.0335369 (2024)
 *           Function word frequencies are "resistant to intentional manipulation."
 */
final class FunctionWordAnalyser
{
    private const int   MIN_WORDS        = 50;
    // Tuned against 136 fixtures:
    //   < 40%: 12% human vs 33% AI (+21pp gap) — tightened from 42%
    //   >= 48%: 24% human vs 13% AI (+11pp gap) — tightened from 50%
    private const float HUMAN_THRESHOLD  = 0.48;
    private const float ALERT_THRESHOLD  = 0.40;

    /** @var array<string, true> */
    private static array $functionWords = [];

    private const array FUNCTION_WORD_LIST = [
        // Articles
        'a', 'an', 'the',
        // Prepositions
        'in', 'on', 'at', 'to', 'for', 'with', 'by', 'from', 'of', 'about',
        'through', 'during', 'before', 'after', 'between', 'into', 'against',
        'above', 'below', 'under', 'over', 'among', 'within', 'without',
        'beyond', 'upon', 'across', 'along', 'toward', 'towards', 'around',
        // Pronouns
        'i', 'you', 'he', 'she', 'it', 'we', 'they', 'me', 'him', 'her',
        'us', 'them', 'my', 'your', 'his', 'its', 'our', 'their', 'mine',
        'yours', 'hers', 'ours', 'theirs', 'this', 'that', 'these', 'those',
        'who', 'whom', 'whose', 'which', 'what', 'myself', 'yourself', 'itself',
        'ourselves', 'themselves', 'himself', 'herself',
        // Auxiliaries / modals
        'is', 'am', 'are', 'was', 'were', 'be', 'been', 'being',
        'have', 'has', 'had', 'do', 'does', 'did',
        'will', 'would', 'shall', 'should', 'can', 'could', 'may', 'might', 'must',
        // Conjunctions
        'and', 'but', 'or', 'nor', 'yet', 'so', 'for', 'because', 'although',
        'while', 'if', 'when', 'since', 'unless', 'until', 'whether', 'though',
        'as', 'than', 'once',
        // Other function words
        'not', 'no', 'very', 'just', 'also', 'too', 'then', 'here', 'there',
        'now', 'how', 'all', 'each', 'every', 'both', 'few', 'more', 'most',
        'other', 'some', 'such', 'any', 'only', 'own', 'same', 'still',
    ];

    public function analyse(string $text): LinguisticFactor
    {
        $words = preg_split('/\s+/u', mb_strtolower(trim($text)), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $words = array_map(
            static fn(string $w): string => preg_replace('/[^\p{L}\p{N}\'-]/u', '', $w) ?? '',
            $words,
        );
        $words = array_values(array_filter($words, static fn(string $w): bool => $w !== ''));

        if (count($words) < self::MIN_WORDS) {
            return new LinguisticFactor(
                confidence: 'HIGH',
                label: 'Function Word Density',
                rawValue: 0.0,
                displayValue: 'Not enough text',
                status: 'insufficient',
                explanation: 'At least 50 words are required to assess function word density.',
            );
        }

        if (empty(self::$functionWords)) {
            foreach (self::FUNCTION_WORD_LIST as $w) {
                self::$functionWords[$w] = true;
            }
        }

        $funcCount = 0;
        foreach ($words as $w) {
            if (isset(self::$functionWords[$w])) {
                $funcCount++;
            }
        }

        $rate = $funcCount / count($words);

        $status = match (true) {
            $rate >= self::HUMAN_THRESHOLD => 'human_signal',
            $rate >= self::ALERT_THRESHOLD => 'normal',
            default                        => 'alert',
        };

        $explanation = sprintf(
            '%d of %d words (%.1f%%) are function words (articles, prepositions, pronouns, auxiliaries, conjunctions). ',
            $funcCount,
            count($words),
            $rate * 100,
        );

        $explanation .= match ($status) {
            'human_signal' => 'A high function word density is characteristic of natural human writing (Arxiv 2407.03646).',
            'normal'       => 'Function word density is within the normal range.',
            default        => 'A low function word density suggests content-word-heavy writing, a pattern associated with AI-generated text.',
        };

        return new LinguisticFactor(
            confidence: 'HIGH',
            label: 'Function Word Density',
            rawValue: $rate * 100,
            displayValue: sprintf('%.1f%%', $rate * 100),
            status: $status,
            explanation: $explanation,
        );
    }
}
