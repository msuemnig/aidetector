<?php

declare(strict_types=1);

namespace App\Services\AiDetector\Linguistic;

use App\Services\AiDetector\DTOs\LinguisticFactor;

/**
 * Measures how varied the first words of sentences are.
 *
 * AI text frequently starts sentences with the same transitional phrases
 * ("Furthermore," "Additionally," "Moreover," "In addition," "It is").
 * Human writers naturally vary their sentence openers.
 *
 * Metric: unique first-words / total sentences
 *
 * Thresholds:
 *   ratio > 0.75 → human_signal (highly varied openers)
 *   ratio 0.50–0.75 → normal
 *   ratio < 0.50 → alert (repetitive openers, AI-like)
 *
 * Citation: Shaib et al. (2024) "Detection and Measurement of Syntactic
 *           Templates in Generated Text" EMNLP 2024.
 */
final class SentenceOpenerDiversityAnalyser
{
    private const int   MIN_SENTENCES    = 8;
    private const float HUMAN_THRESHOLD  = 0.75;
    private const float ALERT_THRESHOLD  = 0.50;

    public function analyse(string $text): LinguisticFactor
    {
        $sentences = preg_split('/[.!?]+\s+/u', trim($text), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $sentences = array_values(array_filter(
            array_map('trim', $sentences),
            static fn(string $s): bool => str_word_count($s) > 0,
        ));

        if (count($sentences) < self::MIN_SENTENCES) {
            return new LinguisticFactor(
                confidence: 'HIGH',
                label: 'Sentence Opener Diversity',
                rawValue: 0.0,
                displayValue: 'Not enough sentences',
                status: 'insufficient',
                explanation: sprintf('At least %d sentences are required to assess opener diversity.', self::MIN_SENTENCES),
            );
        }

        // Extract the first word of each sentence (lowercased)
        $openers = [];
        foreach ($sentences as $sentence) {
            $words = preg_split('/\s+/u', $sentence, 2);
            if (isset($words[0])) {
                $openers[] = mb_strtolower(preg_replace('/[^\p{L}\p{N}]/u', '', $words[0]) ?? '');
            }
        }

        $openers     = array_filter($openers, static fn(string $w): bool => $w !== '');
        $totalCount  = count($openers);
        $uniqueCount = count(array_unique($openers));
        $ratio       = $totalCount > 0 ? $uniqueCount / $totalCount : 0.0;

        $status = match (true) {
            $ratio >= self::HUMAN_THRESHOLD => 'human_signal',
            $ratio >= self::ALERT_THRESHOLD => 'normal',
            default                         => 'alert',
        };

        // Find the most repeated openers for the explanation
        $freq = array_count_values($openers);
        arsort($freq);
        $topRepeated = array_slice(
            array_filter($freq, static fn(int $c): bool => $c >= 2),
            0,
            3,
            true,
        );

        $explanation = sprintf(
            '%d unique openers out of %d sentences (%.0f%% diversity). ',
            $uniqueCount,
            $totalCount,
            $ratio * 100,
        );

        if (!empty($topRepeated)) {
            $repeatedList = implode(', ', array_map(
                fn(string $word, int $count): string => "\"{$word}\" ({$count}×)",
                array_keys($topRepeated),
                array_values($topRepeated),
            ));
            $explanation .= "Most repeated: {$repeatedList}. ";
        }

        $explanation .= match ($status) {
            'human_signal' => 'High opener diversity is characteristic of natural human writing.',
            'normal'       => 'Sentence opener diversity is within the normal range.',
            default        => 'Low opener diversity — many sentences start the same way, a pattern seen in AI-generated text (Shaib et al., EMNLP 2024).',
        };

        return new LinguisticFactor(
            confidence: 'HIGH',
            label: 'Sentence Opener Diversity',
            rawValue: $ratio * 100,
            displayValue: sprintf('%.0f%% unique', $ratio * 100),
            status: $status,
            explanation: $explanation,
        );
    }
}
