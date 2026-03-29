<?php

declare(strict_types=1);

namespace App\Services\AiDetector\Linguistic;

use App\Services\AiDetector\DTOs\LinguisticFactor;

/**
 * Measures the proportion of rare (uncommon) words in the provided text.
 *
 * Words are compared against a list of common English words loaded from
 * resources/data/common_words.txt. Short words (under 3 characters) are
 * treated as stop words and excluded from the calculation.
 *
 * AI-generated text sometimes uses an unnaturally wide vocabulary (high rare-word
 * rate) or, conversely, stays suspiciously close to common words (very low rate).
 *
 * Thresholds:
 *    < 3%   → warning  (suspiciously common vocabulary)
 *    3–8%   → normal
 *    8–12%  → warning
 *    > 12%  → alert
 */
final class RareWordAnalyser
{
    private const int   MIN_WORDS         = 50;
    private const int   MIN_WORD_LENGTH   = 3;

    private const float LOW_WARNING_MAX   = 3.0;
    private const float NORMAL_MAX        = 8.0;
    private const float WARNING_MAX       = 12.0;

    /**
     * Cached common-word set, shared across all instances and calls.
     *
     * @var array<string, true>|null
     */
    private static ?array $commonWords = null;

    /**
     * Analyse the rare-word rate of the provided text.
     */
    public function analyse(string $text): LinguisticFactor
    {
        $tokens = $this->tokenise($text);

        if (count($tokens) < self::MIN_WORDS) {
            return new LinguisticFactor(
                label: 'Rare Word Rate',
                rawValue: 0.0,
                displayValue: 'Not enough text',
                status: 'insufficient',
                explanation: 'At least 50 qualifying words (3+ characters) are required to assess rare word usage.',
            );
        }

        $commonWords = $this->loadCommonWords();
        $rareCount   = 0;

        foreach ($tokens as $word) {
            if (!isset($commonWords[$word])) {
                $rareCount++;
            }
        }

        $total  = count($tokens);
        $rate   = ($rareCount / $total) * 100;
        $status = $this->determineStatus($rate);

        return new LinguisticFactor(
            label: 'Rare Word Rate',
            rawValue: $rate,
            displayValue: number_format($rate, 1) . '%',
            status: $status,
            explanation: $this->buildExplanation($rate, $rareCount, $total, $status),
        );
    }

    /**
     * Tokenise text: lowercase, strip punctuation, filter stop words (< 3 chars).
     *
     * @return list<string>
     */
    private function tokenise(string $text): array
    {
        $lower = mb_strtolower($text);
        $clean = preg_replace("/[^\p{L}\p{N}'\s]/u", ' ', $lower);
        $words = preg_split('/\s+/u', trim($clean ?? ''), flags: PREG_SPLIT_NO_EMPTY);

        return array_values(array_filter(
            $words ?? [],
            static fn(string $w): bool => mb_strlen($w) >= self::MIN_WORD_LENGTH,
        ));
    }

    /**
     * Load the common-word list from disk, caching it in a static property.
     *
     * @return array<string, true>
     */
    private function loadCommonWords(): array
    {
        if (self::$commonWords !== null) {
            return self::$commonWords;
        }

        $path = resource_path('data/common_words.txt');

        if (!file_exists($path)) {
            // Gracefully degrade: treat every word as common so the rate is 0%.
            self::$commonWords = [];
            return self::$commonWords;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        self::$commonWords = [];
        foreach ($lines ?? [] as $line) {
            $word = mb_strtolower(trim($line));
            if ($word !== '') {
                self::$commonWords[$word] = true;
            }
        }

        return self::$commonWords;
    }

    /**
     * Determine the status band for a given rare-word rate.
     */
    private function determineStatus(float $rate): string
    {
        return match (true) {
            $rate < self::LOW_WARNING_MAX => 'warning',   // Suspiciously low.
            $rate <= self::NORMAL_MAX     => 'normal',
            $rate <= self::WARNING_MAX    => 'warning',
            default                       => 'alert',
        };
    }

    /**
     * Build a plain-English explanation of the rare-word rate result.
     */
    private function buildExplanation(float $rate, int $rareCount, int $total, string $status): string
    {
        $base = sprintf(
            '%d of %d qualifying words (%s%%) were not found in the common-word list. '
            . 'A rate between 3%% and 8%% is typical of natural writing.',
            $rareCount,
            $total,
            number_format($rate, 1),
        );

        return match ($status) {
            'normal'  => $base . ' The vocabulary mix appears natural.',
            'alert'   => $base . ' A very high proportion of uncommon words may indicate artificially elevated vocabulary, a trait sometimes seen in AI-generated text.',
            default   => $rate < self::LOW_WARNING_MAX
                ? $base . ' Suspiciously common vocabulary — the text may lack the lexical variety expected of human writing.'
                : $base . ' Slightly elevated rare-word usage — worth noting but not conclusive on its own.',
        };
    }
}
