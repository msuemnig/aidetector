<?php

declare(strict_types=1);

namespace App\Services\AiDetector\Linguistic;

use App\Services\AiDetector\DTOs\LinguisticFactor;

/**
 * Measures variation in sentence length using the Coefficient of Variation (CV).
 *
 * CV = standard deviation / mean of sentence word counts.
 * Human writing tends to have high CV because sentence lengths vary naturally.
 * AI-generated text often exhibits suspiciously uniform sentence lengths,
 * resulting in a low CV.
 */
final class SentenceLengthAnalyser
{
    private const int   MIN_WORDS     = 50;
    // Human signal disabled — fires 18% human vs 15% AI at 0.65, no discrimination
    private const float HUMAN_CV      = 99.0;  // effectively disabled
    private const float NORMAL_CV     = 0.45;
    private const float WARNING_CV    = 0.35;

    /**
     * Analyse sentence-length variation in the provided text.
     */
    public function analyse(string $text): LinguisticFactor
    {
        // Quick word count gate before heavier processing.
        $wordCount = str_word_count($text);

        if ($wordCount < self::MIN_WORDS) {
            return new LinguisticFactor(
                label: 'Sentence Length Variation (CV)',
                rawValue: 0.0,
                displayValue: 'Not enough text',
                status: 'insufficient',
                explanation: 'At least 50 words are required to assess sentence-length variation reliably.',
            );
        }

        $sentences = $this->splitSentences($text);

        // Filter out empty segments that result from splitting.
        $lengths = array_values(array_filter(
            array_map(static fn(string $s): int => str_word_count(trim($s)), $sentences),
            static fn(int $len): bool => $len > 0,
        ));

        if (count($lengths) < 2) {
            return new LinguisticFactor(
                label: 'Sentence Length Variation (CV)',
                rawValue: 0.0,
                displayValue: 'Not enough text',
                status: 'insufficient',
                explanation: 'At least two sentences are required to calculate the Coefficient of Variation.',
            );
        }

        $mean   = array_sum($lengths) / count($lengths);
        $stddev = $this->standardDeviation($lengths, $mean);
        $cv     = $mean > 0 ? $stddev / $mean : 0.0;
        $status = $this->determineStatus($cv);

        return new LinguisticFactor(
            label: 'Sentence Length Variation (CV)',
            rawValue: $cv,
            displayValue: number_format($cv * 100, 1) . '%',
            status: $status,
            explanation: $this->buildExplanation($cv, $mean, count($lengths), $status),
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

        return array_values($parts ?? []);
    }

    /**
     * Calculate the population standard deviation of an array of integers.
     *
     * @param list<int> $values
     */
    private function standardDeviation(array $values, float $mean): float
    {
        $squaredDiffs = array_map(
            static fn(int $v): float => ($v - $mean) ** 2,
            $values,
        );

        return sqrt(array_sum($squaredDiffs) / count($values));
    }

    /**
     * Determine the status band for a given CV value.
     */
    private function determineStatus(float $cv): string
    {
        return match (true) {
            $cv >= self::HUMAN_CV   => 'human_signal',
            $cv >= self::NORMAL_CV  => 'normal',
            $cv >= self::WARNING_CV => 'warning',
            default                 => 'alert',
        };
    }

    /**
     * Build a plain-English explanation of the CV result.
     */
    private function buildExplanation(float $cv, float $mean, int $sentenceCount, string $status): string
    {
        $cvPercent = number_format($cv * 100, 1);
        $meanWords = number_format($mean, 1);

        $base = sprintf(
            'The Coefficient of Variation for sentence length is %s%% across %d %s (mean: %s words per sentence). '
            . 'A CV of 45%% or above is typical of natural human writing.',
            $cvPercent,
            $sentenceCount,
            $sentenceCount === 1 ? 'sentence' : 'sentences',
            $meanWords,
        );

        return match ($status) {
            'human_signal' => $base . ' High variation in sentence length is a strong indicator of natural human writing (Desaire et al., 2023).',
            'normal'  => $base . ' This text shows healthy variation in sentence length.',
            'warning' => $base . ' Sentence lengths are somewhat uniform, which may warrant further review.',
            default   => $base . ' Sentence lengths are unusually consistent — a pattern commonly associated with AI-generated text.',
        };
    }
}
