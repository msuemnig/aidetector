<?php

declare(strict_types=1);

namespace App\Services\AiDetector\Linguistic;

use App\Services\AiDetector\DTOs\LinguisticFactor;

/**
 * Measures the hapax legomenon rate — the proportion of unique words that appear
 * exactly once in the text.
 *
 * Human writers naturally use more "one-off" words drawn from personal vocabulary.
 * AI text shows a "rich-get-richer" effect where common tokens dominate and the
 * count of words-used-once drops.
 *
 * Thresholds (hapax / unique words):
 *   > 0.55 → human_signal (strong human indicator)
 *   0.40–0.55 → normal
 *   < 0.40 → alert (AI-like collapsed vocabulary)
 *
 * Citation: Arxiv 2410.12341; MDPI Information 2078-2489 (2024)
 */
final class HapaxLegomenonAnalyser
{
    private const int   MIN_WORDS       = 100;
    private const float HUMAN_THRESHOLD = 0.55;
    private const float ALERT_THRESHOLD = 0.40;

    public function analyse(string $text): LinguisticFactor
    {
        $words = preg_split('/\s+/u', mb_strtolower(trim($text)), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        // Strip punctuation from each token
        $words = array_values(array_filter(
            array_map(static fn(string $w): string => preg_replace('/[^\p{L}\p{N}\'-]/u', '', $w) ?? '', $words),
            static fn(string $w): bool => $w !== '',
        ));

        if (count($words) < self::MIN_WORDS) {
            return new LinguisticFactor(
                confidence: 'HIGH',
                label: 'Hapax Legomenon Rate',
                rawValue: 0.0,
                displayValue: 'Not enough text',
                status: 'insufficient',
                explanation: 'At least 100 words are required to assess hapax legomenon rate reliably.',
            );
        }

        $freq       = array_count_values($words);
        $uniqueCount = count($freq);
        $hapaxCount  = count(array_filter($freq, static fn(int $c): bool => $c === 1));
        $rate        = $uniqueCount > 0 ? $hapaxCount / $uniqueCount : 0.0;

        $status = match (true) {
            $rate >= self::HUMAN_THRESHOLD => 'human_signal',
            $rate >= self::ALERT_THRESHOLD => 'normal',
            default                        => 'alert',
        };

        $explanation = sprintf(
            '%d of %d unique words (%.1f%%) appear exactly once across %d total words. ',
            $hapaxCount,
            $uniqueCount,
            $rate * 100,
            count($words),
        );

        $explanation .= match ($status) {
            'human_signal' => 'A high hapax rate indicates diverse, natural vocabulary — a strong human writing indicator (Arxiv 2410.12341).',
            'normal'       => 'The hapax rate is within the normal range.',
            default        => 'A low hapax rate suggests vocabulary is concentrated around common tokens — a pattern seen in AI-generated text.',
        };

        return new LinguisticFactor(
            confidence: 'HIGH',
            label: 'Hapax Legomenon Rate',
            rawValue: $rate * 100,
            displayValue: sprintf('%.1f%%', $rate * 100),
            status: $status,
            explanation: $explanation,
        );
    }
}
