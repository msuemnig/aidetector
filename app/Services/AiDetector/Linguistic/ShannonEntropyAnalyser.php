<?php

declare(strict_types=1);

namespace App\Services\AiDetector\Linguistic;

use App\Services\AiDetector\DTOs\LinguisticFactor;

/**
 * Measures Shannon entropy of the word frequency distribution.
 *
 * Shannon entropy quantifies the unpredictability of word choices. AI text
 * tends toward lower entropy (below 4.0 bits/word) because LLMs favour
 * high-probability tokens, while human text typically falls between 4.0 and 6.0.
 *
 * Formula: H = -SUM(p(w) * log2(p(w))) for each unique word w
 *
 * Thresholds:
 *   H > 5.5 → human_signal (rich, unpredictable vocabulary)
 *   H 4.0–5.5 → normal
 *   H < 4.0 → alert (predictable, AI-like word choices)
 *
 * Citation: Shannon (1948). Applied to AI detection per Venkatraman et al.
 *           (2024) "GPT-who" NAACL Findings 2024; hastewire.com detection guide.
 */
final class ShannonEntropyAnalyser
{
    private const int   MIN_WORDS        = 100;
    private const float HUMAN_THRESHOLD  = 5.5;
    private const float NORMAL_THRESHOLD = 4.0;

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
                label: 'Shannon Entropy',
                rawValue: 0.0,
                displayValue: 'Not enough text',
                status: 'insufficient',
                explanation: 'At least 100 words are required to assess Shannon entropy reliably.',
            );
        }

        $total   = count($words);
        $freq    = array_count_values($words);
        $entropy = 0.0;

        foreach ($freq as $count) {
            $p = $count / $total;
            $entropy -= $p * log($p, 2);
        }

        $status = match (true) {
            $entropy >= self::HUMAN_THRESHOLD  => 'human_signal',
            $entropy >= self::NORMAL_THRESHOLD => 'normal',
            default                            => 'alert',
        };

        $explanation = sprintf(
            'Shannon entropy is %.2f bits/word across %d words (%d unique). ',
            $entropy,
            $total,
            count($freq),
        );

        $explanation .= match ($status) {
            'human_signal' => 'High entropy indicates diverse, unpredictable word choices — characteristic of human writing (Venkatraman et al., 2024).',
            'normal'       => 'Entropy is within the normal range for natural text.',
            default        => 'Low entropy indicates predictable, repetitive word choices — a pattern associated with AI-generated text.',
        };

        return new LinguisticFactor(
            confidence: 'HIGH',
            label: 'Shannon Entropy',
            rawValue: $entropy,
            displayValue: sprintf('%.2f bits/word', $entropy),
            status: $status,
            explanation: $explanation,
        );
    }
}
