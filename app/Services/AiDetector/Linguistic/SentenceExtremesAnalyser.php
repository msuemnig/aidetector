<?php

declare(strict_types=1);

namespace App\Services\AiDetector\Linguistic;

use App\Services\AiDetector\DTOs\LinguisticFactor;

/**
 * Measures the proportion of sentences at length extremes (very short or very long).
 *
 * Humans naturally produce both very short sentences (<11 words) and very long
 * sentences (>34 words). AI avoids the extremes, clustering in the 12-30 word range.
 * A high proportion of extreme-length sentences is a human signal; a low proportion
 * is an AI signal.
 *
 * Thresholds (fraction of sentences that are <11 or >34 words):
 *   >= 0.35 → human_signal (many extreme-length sentences)
 *   0.15–0.35 → normal
 *   < 0.15 → alert (AI avoids extremes)
 *
 * Citation: Desaire et al. (2023). "Distinguishing academic science writing from
 *           humans or ChatGPT with over 99% accuracy." Cell Reports Physical Science,
 *           4(6), 101426. PMC10328544.
 */
final class SentenceExtremesAnalyser
{
    private const int   MIN_SENTENCES     = 8;
    private const int   SHORT_THRESHOLD   = 11;  // words — below this = "short"
    private const int   LONG_THRESHOLD    = 34;  // words — above this = "long"
    private const float HUMAN_THRESHOLD   = 0.35;
    private const float ALERT_THRESHOLD   = 0.15;

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
                label: 'Sentence Extremes Ratio',
                rawValue: 0.0,
                displayValue: 'Not enough sentences',
                status: 'insufficient',
                explanation: sprintf('At least %d sentences are required.', self::MIN_SENTENCES),
            );
        }

        $shortCount = count(array_filter($lengths, static fn(int $l): bool => $l < self::SHORT_THRESHOLD));
        $longCount  = count(array_filter($lengths, static fn(int $l): bool => $l > self::LONG_THRESHOLD));
        $extremes   = $shortCount + $longCount;
        $ratio      = $extremes / count($lengths);

        $status = match (true) {
            $ratio >= self::HUMAN_THRESHOLD => 'human_signal',
            $ratio >= self::ALERT_THRESHOLD => 'normal',
            default                         => 'alert',
        };

        $explanation = sprintf(
            '%d of %d sentences (%.0f%%) are at length extremes (%d short < %d words, %d long > %d words). ',
            $extremes,
            count($lengths),
            $ratio * 100,
            $shortCount,
            self::SHORT_THRESHOLD,
            $longCount,
            self::LONG_THRESHOLD,
        );

        $explanation .= match ($status) {
            'human_signal' => 'A high proportion of extreme-length sentences is characteristic of natural human writing (Desaire et al., 2023).',
            'normal'       => 'The proportion of extreme-length sentences is within the normal range.',
            default        => 'AI text avoids very short and very long sentences, clustering in the middle range (Desaire et al., 2023).',
        };

        return new LinguisticFactor(
            confidence: 'HIGH',
            label: 'Sentence Extremes Ratio',
            rawValue: $ratio * 100,
            displayValue: sprintf('%.0f%% extreme', $ratio * 100),
            status: $status,
            explanation: $explanation,
        );
    }
}
