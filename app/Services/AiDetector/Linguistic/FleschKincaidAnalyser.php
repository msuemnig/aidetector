<?php

declare(strict_types=1);

namespace App\Services\AiDetector\Linguistic;

use App\Services\AiDetector\DTOs\LinguisticFactor;

/**
 * Calculates the Flesch-Kincaid Grade Level of the provided text.
 *
 * Formula: 0.39 * (words / sentences) + 11.8 * (syllables / words) - 15.59
 *
 * Syllables are estimated by counting contiguous vowel groups per word,
 * with a minimum of one syllable per word.
 *
 * AI-generated text frequently targets a formal register, which often pushes
 * the reading grade above what is typical for general-audience writing.
 */
final class FleschKincaidAnalyser
{
    private const int   MIN_WORDS    = 50;
    private const float NORMAL_MAX   = 12.0;
    private const float WARNING_MAX  = 14.0;

    // FK formula coefficients.
    private const float COEFF_ASL    = 0.39;   // Average Sentence Length coefficient.
    private const float COEFF_ASW    = 11.8;   // Average Syllables per Word coefficient.
    private const float CONSTANT     = 15.59;  // Constant offset.

    private const string VOWELS = 'aeiouAEIOU';

    /**
     * Analyse the Flesch-Kincaid grade level of the provided text.
     */
    public function analyse(string $text): LinguisticFactor
    {
        if (str_word_count($text) < self::MIN_WORDS) {
            return new LinguisticFactor(
                label: 'Flesch-Kincaid Grade Level',
                rawValue: 0.0,
                displayValue: 'Not enough text',
                status: 'insufficient',
                explanation: 'At least 50 words are required to calculate a meaningful Flesch-Kincaid grade.',
            );
        }

        $sentences     = $this->splitSentences($text);
        $sentenceCount = count($sentences);

        if ($sentenceCount === 0) {
            return new LinguisticFactor(
                label: 'Flesch-Kincaid Grade Level',
                rawValue: 0.0,
                displayValue: 'Not enough text',
                status: 'insufficient',
                explanation: 'No complete sentences could be identified in the provided text.',
            );
        }

        $words         = $this->tokenise($text);
        $wordCount     = count($words);
        $syllableCount = array_sum(array_map($this->countSyllables(...), $words));

        $asl   = $wordCount / $sentenceCount;
        $asw   = $syllableCount / $wordCount;
        $grade = (self::COEFF_ASL * $asl) + (self::COEFF_ASW * $asw) - self::CONSTANT;

        // FK grade can theoretically be negative for very simple text; clamp to 0.
        $grade  = max(0.0, $grade);
        $status = $this->determineStatus($grade);

        return new LinguisticFactor(
            label: 'Flesch-Kincaid Grade Level',
            rawValue: $grade,
            displayValue: 'Grade ' . number_format($grade, 1),
            status: $status,
            explanation: $this->buildExplanation($grade, $status),
        );
    }

    /**
     * Count the estimated number of syllables in a single word.
     *
     * Syllables are counted as contiguous runs of vowels, with a minimum of 1.
     */
    private function countSyllables(string $word): int
    {
        // Strip non-alpha characters (e.g. hyphens, apostrophes).
        $clean = preg_replace('/[^a-zA-Z]/u', '', $word) ?? '';

        if ($clean === '') {
            return 1;
        }

        // Count transitions into a vowel group.
        $count  = 0;
        $inVowel = false;

        $length = strlen($clean);
        for ($i = 0; $i < $length; $i++) {
            $isVowel = str_contains(self::VOWELS, $clean[$i]);

            if ($isVowel && !$inVowel) {
                $count++;
            }

            $inVowel = $isVowel;
        }

        return max(1, $count);
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
     * Tokenise text into words, stripping punctuation.
     *
     * @return list<string>
     */
    private function tokenise(string $text): array
    {
        $clean = preg_replace("/[^\p{L}\p{N}'\s]/u", ' ', $text);
        $words = preg_split('/\s+/u', trim($clean ?? ''), flags: PREG_SPLIT_NO_EMPTY);

        return array_values(array_filter($words ?? [], static fn(string $w): bool => $w !== ''));
    }

    /**
     * Determine the status band for a given FK grade.
     */
    private function determineStatus(float $grade): string
    {
        return match (true) {
            $grade < self::NORMAL_MAX  => 'normal',
            $grade <= self::WARNING_MAX => 'warning',
            default                    => 'alert',
        };
    }

    /**
     * Build a plain-English explanation of the FK grade result.
     */
    private function buildExplanation(float $grade, string $status): string
    {
        $formatted = number_format($grade, 1);

        $base = "The Flesch-Kincaid Grade Level is {$formatted}, which corresponds roughly to a "
            . "US school grade {$formatted} reading level. "
            . 'Grades below 12 are typical for general-audience writing.';

        return match ($status) {
            'normal'  => $base . ' This text is within an accessible, natural reading level.',
            'warning' => $base . ' The text is moderately complex, which may indicate a formal or AI-influenced style.',
            default   => $base . ' Highly elevated reading levels are associated with AI text that aims to sound sophisticated.',
        };
    }
}
