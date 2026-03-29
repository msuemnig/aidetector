<?php

declare(strict_types=1);

namespace App\Services\AiDetector\Linguistic;

use App\Services\AiDetector\DTOs\LinguisticFactor;

/**
 * Measures the trigram (3-word sequence) repetition rate.
 *
 * AI text reuses phrase templates at significantly higher rates than human text
 * because LLMs fall back on high-probability sequences. This analyser counts
 * the fraction of unique trigrams that appear more than once.
 *
 * Thresholds (repeated trigrams / total unique trigrams):
 *   < 0.05 → human_signal (very few repeated phrases)
 *   0.05–0.15 → normal
 *   > 0.15 → alert (high repetition, AI-like)
 *
 * Citation: Gehrmann et al. (2019) "GLTR" ACL 2019;
 *           Alibaba LLM N-Gram Repetition Filter; EMNLP 2024 syntactic templates.
 */
final class NgramRepetitionAnalyser
{
    private const int   MIN_WORDS        = 100;
    private const int   NGRAM_SIZE       = 3;
    private const float HUMAN_THRESHOLD  = 0.05;
    private const float ALERT_THRESHOLD  = 0.15;

    public function analyse(string $text): LinguisticFactor
    {
        $words = preg_split('/\s+/u', mb_strtolower(trim($text)), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $words = array_values(array_filter(
            array_map(static fn(string $w): string => preg_replace('/[^\p{L}\p{N}\'-]/u', '', $w) ?? '', $words),
            static fn(string $w): bool => $w !== '',
        ));

        if (count($words) < self::MIN_WORDS) {
            return new LinguisticFactor(
                confidence: 'HIGH',
                label: 'N-gram Repetition Rate',
                rawValue: 0.0,
                displayValue: 'Not enough text',
                status: 'insufficient',
                explanation: 'At least 100 words are required to assess trigram repetition reliably.',
            );
        }

        // Build trigrams
        $trigrams = [];
        $limit    = count($words) - self::NGRAM_SIZE;
        for ($i = 0; $i <= $limit; $i++) {
            $trigrams[] = $words[$i] . ' ' . $words[$i + 1] . ' ' . $words[$i + 2];
        }

        if (count($trigrams) === 0) {
            return new LinguisticFactor(
                confidence: 'HIGH',
                label: 'N-gram Repetition Rate',
                rawValue: 0.0,
                displayValue: 'Not enough text',
                status: 'insufficient',
                explanation: 'Not enough words to form trigrams.',
            );
        }

        $freq         = array_count_values($trigrams);
        $uniqueCount  = count($freq);
        $repeatedCount = count(array_filter($freq, static fn(int $c): bool => $c >= 2));
        $rate          = $uniqueCount > 0 ? $repeatedCount / $uniqueCount : 0.0;

        $status = match (true) {
            $rate <= self::HUMAN_THRESHOLD => 'human_signal',
            $rate <= self::ALERT_THRESHOLD => 'normal',
            default                        => 'alert',
        };

        $explanation = sprintf(
            '%d of %d unique trigrams (%.1f%%) appear more than once across %d total trigrams. ',
            $repeatedCount,
            $uniqueCount,
            $rate * 100,
            count($trigrams),
        );

        $explanation .= match ($status) {
            'human_signal' => 'Very few repeated phrases — characteristic of varied human writing.',
            'normal'       => 'Trigram repetition is within the normal range.',
            default        => 'High trigram repetition indicates formulaic phrasing — a pattern associated with AI-generated text (EMNLP 2024).',
        };

        return new LinguisticFactor(
            confidence: 'HIGH',
            label: 'N-gram Repetition Rate',
            rawValue: $rate * 100,
            displayValue: sprintf('%.1f%% repeated', $rate * 100),
            status: $status,
            explanation: $explanation,
        );
    }
}
