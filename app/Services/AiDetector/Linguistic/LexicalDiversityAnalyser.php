<?php

declare(strict_types=1);

namespace App\Services\AiDetector\Linguistic;

use App\Services\AiDetector\DTOs\LinguisticFactor;

/**
 * Measures lexical diversity using the Type-Token Ratio (TTR).
 *
 * TTR = unique words / total words, after lowercasing and stripping punctuation.
 * A high TTR suggests a rich, varied vocabulary; a very low TTR may indicate
 * repetitive or formulaic writing patterns common in AI-generated text.
 */
final class LexicalDiversityAnalyser
{
    private const int    MIN_WORDS      = 50;
    private const float  NORMAL_LOW     = 0.60;
    private const float  NORMAL_HIGH    = 0.85;

    /**
     * Analyse the lexical diversity of the provided text.
     */
    public function analyse(string $text): LinguisticFactor
    {
        $words = $this->tokenise($text);
        $total = count($words);

        if ($total < self::MIN_WORDS) {
            return new LinguisticFactor(
                label: 'Lexical Diversity (TTR)',
                rawValue: 0.0,
                displayValue: 'Not enough text',
                status: 'insufficient',
                explanation: 'At least 50 words are required to calculate a reliable Type-Token Ratio.',
            );
        }

        $unique = count(array_unique($words));
        $ttr    = $unique / $total;
        $status = $this->determineStatus($ttr);

        return new LinguisticFactor(
            label: 'Lexical Diversity (TTR)',
            rawValue: $ttr,
            displayValue: number_format($ttr * 100, 1) . '%',
            status: $status,
            explanation: $this->buildExplanation($ttr, $unique, $total, $status),
        );
    }

    /**
     * Determine the status band for a given TTR value.
     */
    private function determineStatus(float $ttr): string
    {
        return ($ttr >= self::NORMAL_LOW && $ttr <= self::NORMAL_HIGH)
            ? 'normal'
            : 'warning';
    }

    /**
     * Build a plain-English explanation of the TTR result.
     */
    private function buildExplanation(float $ttr, int $unique, int $total, string $status): string
    {
        $percentage = number_format($ttr * 100, 1);

        $base = sprintf(
            'The Type-Token Ratio is %s%% (%d unique %s out of %d total). '
            . 'A TTR between 60%% and 85%% is typical of natural, varied writing.',
            $percentage,
            $unique,
            $unique === 1 ? 'word' : 'words',
            $total,
        );

        if ($status === 'normal') {
            return $base . ' This text falls within the expected range, suggesting healthy vocabulary variety.';
        }

        if ($ttr < self::NORMAL_LOW) {
            return $base . ' This lower-than-expected ratio may indicate repetitive or formulaic language,'
                . ' a trait sometimes associated with AI-generated text.';
        }

        return $base . ' This unusually high ratio may indicate overly varied or artificially diverse vocabulary,'
            . ' which can occasionally be a sign of AI-generated prose.';
    }

    /**
     * Tokenise text into lowercase words, stripping punctuation.
     *
     * @return list<string>
     */
    private function tokenise(string $text): array
    {
        // Lowercase the entire string first.
        $lower = mb_strtolower($text);

        // Strip all characters that are not letters, digits, apostrophes, or whitespace.
        $clean = preg_replace("/[^\p{L}\p{N}'\s]/u", ' ', $lower);

        // Split on whitespace and filter out empty strings.
        $words = preg_split('/\s+/u', trim($clean ?? ''), flags: PREG_SPLIT_NO_EMPTY);

        return array_values(array_filter($words ?? [], static fn(string $w): bool => $w !== ''));
    }
}
