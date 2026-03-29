<?php

declare(strict_types=1);

namespace App\Services\AiDetector\Linguistic;

use App\Services\AiDetector\DTOs\LinguisticFactor;

/**
 * Measures the coefficient of variation of paragraph lengths (in words).
 *
 * Human writers produce paragraphs of highly variable length; AI tends toward
 * uniform paragraph sizes. A high CV is a human signal; a low CV is an AI signal.
 *
 * Thresholds (CV of paragraph word counts):
 *   > 0.50 → human_signal
 *   0.30–0.50 → normal
 *   < 0.30 → alert
 *
 * Citation: Desaire et al. (PMC10328544, 2023) — paragraph SD > 25 words
 *           classified as human with >99% accuracy in academic science writing.
 */
final class ParagraphVarianceAnalyser
{
    private const int   MIN_PARAGRAPHS   = 3;
    private const float HUMAN_THRESHOLD  = 0.50;
    private const float ALERT_THRESHOLD  = 0.30;

    public function analyse(string $text): LinguisticFactor
    {
        // Split on double newlines (or \n\n, \r\n\r\n)
        $paragraphs = preg_split('/\n\s*\n/u', trim($text), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $paragraphs = array_values(array_filter(
            array_map('trim', $paragraphs),
            static fn(string $p): bool => str_word_count($p) > 0,
        ));

        if (count($paragraphs) < self::MIN_PARAGRAPHS) {
            return new LinguisticFactor(
                confidence: 'HIGH',
                label: 'Paragraph Length Variance',
                rawValue: 0.0,
                displayValue: 'Not enough paragraphs',
                status: 'insufficient',
                explanation: sprintf('At least %d paragraphs are required to assess paragraph length variance.', self::MIN_PARAGRAPHS),
            );
        }

        $lengths = array_map(static fn(string $p): int => str_word_count($p), $paragraphs);
        $mean    = array_sum($lengths) / count($lengths);
        $sqDiffs = array_map(static fn(int $l): float => ($l - $mean) ** 2, $lengths);
        $stddev  = sqrt(array_sum($sqDiffs) / count($lengths));
        $cv      = $mean > 0 ? $stddev / $mean : 0.0;

        $status = match (true) {
            $cv >= self::HUMAN_THRESHOLD => 'human_signal',
            $cv >= self::ALERT_THRESHOLD => 'normal',
            default                      => 'alert',
        };

        $explanation = sprintf(
            'Paragraph length CV is %.1f%% across %d paragraphs (mean: %.0f words, SD: %.0f words). ',
            $cv * 100,
            count($paragraphs),
            $mean,
            $stddev,
        );

        $explanation .= match ($status) {
            'human_signal' => 'Highly variable paragraph lengths are a strong indicator of human writing (Desaire et al., 2023).',
            'normal'       => 'Paragraph length variation is within the normal range.',
            default        => 'Unusually uniform paragraph lengths are a pattern commonly seen in AI-generated text.',
        };

        return new LinguisticFactor(
            confidence: 'HIGH',
            label: 'Paragraph Length Variance',
            rawValue: $cv * 100,
            displayValue: sprintf('CV %.1f%%', $cv * 100),
            status: $status,
            explanation: $explanation,
        );
    }
}
