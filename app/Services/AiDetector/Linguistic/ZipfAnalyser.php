<?php

declare(strict_types=1);

namespace App\Services\AiDetector\Linguistic;

use App\Services\AiDetector\DTOs\LinguisticFactor;

/**
 * Evaluates how well the text's word-frequency distribution follows Zipf's Law.
 *
 * Zipf's Law states that the frequency of any word is inversely proportional
 * to its rank in the frequency table. Natural text typically conforms well to
 * this distribution. AI-generated text sometimes deviates because models sample
 * from a more uniform distribution or over-use certain tokens.
 *
 * The goodness-of-fit is measured as R² on the log-transformed frequency series:
 *   log(actual_freq) vs log(ideal_zipf_freq)
 *
 * Thresholds:
 *   R² > 0.92  → normal
 *   R² 0.85–0.92 → warning
 *   R² < 0.85  → alert
 *
 * Requires at least 100 words for a meaningful analysis.
 */
final class ZipfAnalyser
{
    private const int   MIN_WORDS    = 100;
    private const float NORMAL_MIN   = 0.92;
    private const float WARNING_MIN  = 0.85;

    /**
     * Analyse how closely the text's word-frequency distribution matches Zipf's Law.
     */
    public function analyse(string $text): LinguisticFactor
    {
        $words = $this->tokenise($text);

        if (count($words) < self::MIN_WORDS) {
            return new LinguisticFactor(
                label: "Zipf's Law Fit (R²)",
                rawValue: 0.0,
                displayValue: 'Not enough text',
                status: 'insufficient',
                explanation: "At least 100 words are required to evaluate conformance to Zipf's Law.",
            );
        }

        $frequencies = $this->buildFrequencyTable($words);
        $r2          = $this->calculateR2($frequencies);
        $status      = $this->determineStatus($r2);

        return new LinguisticFactor(
            label: "Zipf's Law Fit (R²)",
            rawValue: $r2,
            displayValue: 'R² = ' . number_format($r2, 2),
            status: $status,
            explanation: $this->buildExplanation($r2, count($frequencies), count($words), $status),
        );
    }

    /**
     * Tokenise text into lowercase words, stripping punctuation.
     *
     * @return list<string>
     */
    private function tokenise(string $text): array
    {
        $lower = mb_strtolower($text);
        $clean = preg_replace("/[^\p{L}\p{N}'\s]/u", ' ', $lower);
        $words = preg_split('/\s+/u', trim($clean ?? ''), flags: PREG_SPLIT_NO_EMPTY);

        return array_values(array_filter($words ?? [], static fn(string $w): bool => $w !== ''));
    }

    /**
     * Build a frequency table sorted by descending frequency.
     *
     * @param  list<string>    $words
     * @return list<int>       Frequencies in descending order (rank 1 = most frequent).
     */
    private function buildFrequencyTable(array $words): array
    {
        $counts = array_count_values($words);
        arsort($counts);

        return array_values($counts);
    }

    /**
     * Calculate R² of log(actual frequency) vs log(ideal Zipf frequency).
     *
     * The ideal Zipf frequency for rank r is: freq[0] / r
     * (i.e. proportional to 1/rank, scaled so rank-1 matches the top word).
     *
     * R² = 1 - (SS_res / SS_tot)
     *
     * @param  list<int> $frequencies Descending-sorted actual frequencies.
     */
    private function calculateR2(array $frequencies): float
    {
        $n       = count($frequencies);
        $topFreq = $frequencies[0];

        // Build log-transformed actual and ideal arrays.
        $logActual = [];
        $logIdeal  = [];

        for ($rank = 1; $rank <= $n; $rank++) {
            $logActual[] = log($frequencies[$rank - 1] + 1);   // +1 to avoid log(0)
            $logIdeal[]  = log(($topFreq / $rank) + 1);
        }

        $meanActual = array_sum($logActual) / $n;

        $ssTot = 0.0;
        $ssRes = 0.0;

        for ($i = 0; $i < $n; $i++) {
            $ssTot += ($logActual[$i] - $meanActual) ** 2;
            $ssRes += ($logActual[$i] - $logIdeal[$i]) ** 2;
        }

        // Guard against degenerate case where all values are identical (SS_tot = 0).
        if ($ssTot === 0.0) {
            return 1.0;
        }

        $r2 = 1.0 - ($ssRes / $ssTot);

        // Clamp to [-1, 1] — R² can technically be negative for very poor fits.
        return max(-1.0, min(1.0, $r2));
    }

    /**
     * Determine the status band for a given R² value.
     */
    private function determineStatus(float $r2): string
    {
        return match (true) {
            $r2 > self::NORMAL_MIN  => 'normal',
            $r2 >= self::WARNING_MIN => 'warning',
            default                  => 'alert',
        };
    }

    /**
     * Build a plain-English explanation of the Zipf R² result.
     */
    private function buildExplanation(float $r2, int $uniqueWords, int $totalWords, string $status): string
    {
        $base = sprintf(
            "The R² goodness-of-fit against Zipf's Law is %s, calculated across %d unique %s "
            . 'from a total of %d words. '
            . 'Natural text typically achieves R² above 0.92.',
            number_format($r2, 2),
            $uniqueWords,
            $uniqueWords === 1 ? 'word type' : 'word types',
            $totalWords,
        );

        return match ($status) {
            'normal'  => $base . " The word-frequency distribution closely follows Zipf's Law, consistent with natural writing.",
            'warning' => $base . " The distribution shows a modest deviation from Zipf's Law — this may indicate some stylistic irregularity.",
            default   => $base . " A poor fit to Zipf's Law can indicate AI-generated text, where token sampling may produce an atypical frequency distribution.",
        };
    }
}
