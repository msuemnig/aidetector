<?php

declare(strict_types=1);

namespace App\Services\AiDetector\Linguistic;

use App\Services\AiDetector\DTOs\LinguisticFactor;

/**
 * Detects overuse of formal transition words and phrases.
 *
 * AI-generated text frequently employs transitional connectives
 * (e.g. "furthermore", "consequently", "in contrast") at abnormally high rates
 * to create an appearance of logical flow. Transitions are counted when they
 * appear at the start of a sentence or immediately after a comma.
 */
final class TransitionWordAnalyser
{
    private const int   MIN_WORDS   = 50;
    private const float NORMAL_MAX  = 10.0;
    private const float WARNING_MAX = 20.0;

    /**
     * Transition words and phrases to detect.
     * Ordered longest-first so multi-word phrases are matched before substrings.
     */
    private const array TRANSITIONS = [
        'on the other hand',
        'in contrast',
        'as a result',
        'in addition',
        'by contrast',
        'furthermore',
        'consequently',
        'additionally',
        'nevertheless',
        'nonetheless',
        'subsequently',
        'accordingly',
        'therefore',
        'hence',
        'thus',
    ];

    /**
     * Analyse the transition word usage rate in the provided text.
     */
    public function analyse(string $text): LinguisticFactor
    {
        if (str_word_count($text) < self::MIN_WORDS) {
            return new LinguisticFactor(
                confidence: 'MEDIUM',
                label: 'Transition Word Rate',
                rawValue: 0.0,
                displayValue: 'Not enough text',
                status: 'insufficient',
                explanation: 'At least 50 words are required to assess transition word usage reliably.',
            );
        }

        $sentences      = $this->splitSentences($text);
        $totalSentences = count($sentences);

        if ($totalSentences === 0) {
            return new LinguisticFactor(
                confidence: 'MEDIUM',
                label: 'Transition Word Rate',
                rawValue: 0.0,
                displayValue: 'Not enough text',
                status: 'insufficient',
                explanation: 'No complete sentences could be identified in the provided text.',
            );
        }

        $matchedCount = $this->countSentencesWithTransitions($sentences);
        $rate         = ($matchedCount / $totalSentences) * 100;
        $status       = $this->determineStatus($rate);

        return new LinguisticFactor(
            label: 'Transition Word Rate',
            rawValue: $rate,
            displayValue: number_format($rate, 1) . '%',
            status: $status,
            explanation: $this->buildExplanation($rate, $matchedCount, $totalSentences, $status),
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
     * Count how many sentences begin with, or contain after a comma, a transition phrase.
     *
     * @param list<string> $sentences
     */
    private function countSentencesWithTransitions(array $sentences): int
    {
        $count = 0;

        foreach ($sentences as $sentence) {
            if ($this->sentenceHasTransition($sentence)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Determine whether a sentence starts with or uses (after a comma) a transition word.
     */
    private function sentenceHasTransition(string $sentence): bool
    {
        foreach (self::TRANSITIONS as $phrase) {
            $escaped = preg_quote($phrase, '/');

            // Match at the very start of the sentence (optionally after leading whitespace)
            // or immediately following a comma (with optional surrounding spaces).
            $pattern = '/(?:^\s*|,\s*)' . $escaped . '\b/iu';

            if (preg_match($pattern, $sentence)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine the status band for a given transition word rate.
     */
    private function determineStatus(float $rate): string
    {
        return match (true) {
            $rate <= self::NORMAL_MAX  => 'normal',
            $rate <= self::WARNING_MAX => 'warning',
            default                    => 'alert',
        };
    }

    /**
     * Build a plain-English explanation of the transition word result.
     */
    private function buildExplanation(float $rate, int $matched, int $total, string $status): string
    {
        $base = sprintf(
            '%d of %d %s (%s%%) contain a formal transition word or phrase at the start or after a comma. '
            . 'A rate at or below 10%% is considered unremarkable in natural prose.',
            $matched,
            $total,
            $total === 1 ? 'sentence' : 'sentences',
            number_format($rate, 1),
        );

        return match ($status) {
            'normal'  => $base . ' Transition word usage appears natural.',
            'warning' => $base . ' Transition word usage is moderately elevated — a pattern worth noting.',
            default   => $base . ' Heavy use of formal transitions is a strong indicator of AI-generated text.',
        };
    }
}
