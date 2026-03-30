<?php

declare(strict_types=1);

namespace App\Services\AiDetector\Linguistic;

use App\Services\AiDetector\DTOs\LinguisticFactor;

/**
 * Measures the median absolute difference in word count between consecutive sentences.
 *
 * Human writers bounce between short punchy sentences and long complex ones,
 * producing high adjacent deltas. AI tends toward uniformly-sized sentences
 * with small differences between neighbours.
 *
 * Thresholds (median adjacent delta in words):
 *   >= 8 → human_signal (high local choppiness)
 *   4–8  → normal
 *   < 4  → alert (uniform sentence lengths, AI-like)
 *
 * Citation: Desaire et al. (2023). "Distinguishing academic science writing from
 *           humans or ChatGPT with over 99% accuracy." Cell Reports Physical Science,
 *           4(6), 101426. PMC10328544.
 */
final class AdjacentSentenceDeltaAnalyser
{
    private const int   MIN_SENTENCES     = 5;
    private const float HUMAN_THRESHOLD   = 8.0;
    private const float ALERT_THRESHOLD   = 4.0;

    public function analyse(string $text): LinguisticFactor
    {
        $sentences = preg_split('/[.!?](?:\s|$)/u', $text, flags: PREG_SPLIT_NO_EMPTY);
        $lengths = array_values(array_filter(
            array_map(static fn(string $s): int => str_word_count(trim($s)), $sentences ?? []),
            static fn(int $len): bool => $len > 0,
        ));

        if (count($lengths) < self::MIN_SENTENCES) {
            return new LinguisticFactor(
                confidence: 'HIGH',
                label: 'Adjacent Sentence Delta',
                rawValue: 0.0,
                displayValue: 'Not enough sentences',
                status: 'insufficient',
                explanation: sprintf('At least %d sentences are required.', self::MIN_SENTENCES),
            );
        }

        // Compute absolute differences between consecutive sentence lengths
        $deltas = [];
        for ($i = 0; $i < count($lengths) - 1; $i++) {
            $deltas[] = abs($lengths[$i + 1] - $lengths[$i]);
        }

        sort($deltas);
        $median = $deltas[(int) (count($deltas) / 2)];

        $status = match (true) {
            $median >= self::HUMAN_THRESHOLD => 'human_signal',
            $median >= self::ALERT_THRESHOLD => 'normal',
            default                          => 'alert',
        };

        $explanation = sprintf(
            'Median word-count difference between consecutive sentences is %.0f words across %d sentences. ',
            $median,
            count($lengths),
        );

        $explanation .= match ($status) {
            'human_signal' => 'High adjacent delta indicates natural rhythm variation — a human writing signal (Desaire et al., 2023).',
            'normal'       => 'Adjacent sentence delta is within the normal range.',
            default        => 'Low adjacent delta indicates uniformly-sized sentences — a pattern associated with AI-generated text (Desaire et al., 2023).',
        };

        return new LinguisticFactor(
            confidence: 'HIGH',
            label: 'Adjacent Sentence Delta',
            rawValue: (float) $median,
            displayValue: sprintf('%.0f words', $median),
            status: $status,
            explanation: $explanation,
        );
    }
}
