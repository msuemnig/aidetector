<?php

declare(strict_types=1);

namespace App\Services\AiDetector\Linguistic;

use App\Services\AiDetector\DTOs\LinguisticFactor;

/**
 * Analyses the per-sentence rate of stylistically distinctive punctuation marks.
 *
 * Specifically tracks semicolons (;) and em-dashes (— or --), which AI-generated
 * text tends to overuse. Colons and ellipses are also counted but do not
 * independently trigger status changes.
 *
 * Thresholds (per sentence):
 *   Semicolons  > 0.30 → alert
 *   Em-dashes   > 0.40 → alert
 *   Either      > 0.15 → warning (if neither alert threshold is crossed)
 */
final class PunctuationAnalyser
{
    private const int   MIN_WORDS           = 50;

    // Alert thresholds (per sentence).
    private const float SEMICOLON_ALERT     = 0.30;
    private const float EMDASH_ALERT        = 0.40;

    // Warning threshold — applies to either mark individually.
    private const float ELEVATED_WARNING    = 0.15;

    /**
     * Analyse distinctive punctuation usage in the provided text.
     */
    public function analyse(string $text): LinguisticFactor
    {
        if (str_word_count($text) < self::MIN_WORDS) {
            return new LinguisticFactor(
                confidence: 'MEDIUM',
                label: 'Punctuation Pattern',
                rawValue: 0.0,
                displayValue: 'Not enough text',
                status: 'insufficient',
                explanation: 'At least 50 words are required to assess punctuation patterns reliably.',
            );
        }

        $sentences      = $this->splitSentences($text);
        $sentenceCount  = count($sentences);

        if ($sentenceCount === 0) {
            return new LinguisticFactor(
                confidence: 'MEDIUM',
                label: 'Punctuation Pattern',
                rawValue: 0.0,
                displayValue: 'Not enough text',
                status: 'insufficient',
                explanation: 'No complete sentences could be identified in the provided text.',
            );
        }

        $semicolons  = substr_count($text, ';');
        $emDashes    = substr_count($text, '—') + substr_count($text, '--');

        $semicolonRate = $semicolons / $sentenceCount;
        $emDashRate    = $emDashes   / $sentenceCount;
        $rawValue      = max($semicolonRate, $emDashRate);

        $status = $this->determineStatus($semicolonRate, $emDashRate);

        return new LinguisticFactor(
            label: 'Punctuation Pattern',
            rawValue: $rawValue,
            displayValue: sprintf(
                '%s semicolons, %s em-dashes per sentence',
                number_format($semicolonRate, 2),
                number_format($emDashRate, 2),
            ),
            status: $status,
            explanation: $this->buildExplanation($semicolonRate, $emDashRate, $semicolons, $emDashes, $sentenceCount, $status),
        );
    }

    /**
     * Split text into sentences on [.!?] followed by whitespace or end of string.
     *
     * @return list<string>
     */
    private function splitSentences(string $text): array
    {
        $parts = preg_split('/[.!?](?:\s|$)/u', $text, flags: PREG_SPLIT_NO_EMPTY);
        $parts = array_map('trim', $parts ?? []);

        return array_values(array_filter($parts, static fn(string $s): bool => $s !== ''));
    }

    /**
     * Determine the status band based on per-sentence punctuation rates.
     */
    private function determineStatus(float $semicolonRate, float $emDashRate): string
    {
        if ($semicolonRate > self::SEMICOLON_ALERT || $emDashRate > self::EMDASH_ALERT) {
            return 'alert';
        }

        if ($semicolonRate > self::ELEVATED_WARNING || $emDashRate > self::ELEVATED_WARNING) {
            return 'warning';
        }

        return 'normal';
    }

    /**
     * Build a plain-English explanation of the punctuation result.
     */
    private function buildExplanation(
        float  $semicolonRate,
        float  $emDashRate,
        int    $semicolons,
        int    $emDashes,
        int    $sentences,
        string $status,
    ): string {
        $base = sprintf(
            'Found %d %s (%.2f per sentence) and %d %s (%.2f per sentence) across %d %s. '
            . 'Alert thresholds are 0.30 semicolons and 0.40 em-dashes per sentence.',
            $semicolons,
            $semicolons === 1 ? 'semicolon' : 'semicolons',
            $semicolonRate,
            $emDashes,
            $emDashes === 1 ? 'em-dash' : 'em-dashes',
            $emDashRate,
            $sentences,
            $sentences === 1 ? 'sentence' : 'sentences',
        );

        return match ($status) {
            'normal'  => $base . ' Punctuation usage appears natural.',
            'warning' => $base . ' Usage is slightly elevated — this punctuation style is sometimes favoured by AI writing.',
            default   => $base . ' Unusually frequent use of these marks is a pattern commonly seen in AI-generated text.',
        };
    }
}
