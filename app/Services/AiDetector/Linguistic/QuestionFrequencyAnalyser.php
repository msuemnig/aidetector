<?php

declare(strict_types=1);

namespace App\Services\AiDetector\Linguistic;

use App\Services\AiDetector\DTOs\LinguisticFactor;

/**
 * Measures the proportion of sentences that end with a question mark.
 *
 * Human writers naturally embed rhetorical questions, especially in persuasive,
 * reflective, or educational writing. AI-generated text in formal/academic register
 * uses significantly fewer questions.
 *
 * Thresholds (question sentences / total sentences):
 *   >= 0.10 → human_signal (10%+ sentences are questions)
 *   0.03–0.10 → normal
 *   < 0.03 → alert (almost no questions, AI-like)
 *
 * Citation: Desaire et al. (2023). "Distinguishing academic science writing from
 *           humans or ChatGPT with over 99% accuracy." Cell Reports Physical Science,
 *           4(6), 101426. Also in StyloAI (2024) 31-feature set.
 */
final class QuestionFrequencyAnalyser
{
    private const int   MIN_SENTENCES     = 8;
    private const float HUMAN_THRESHOLD   = 0.10;
    private const float ALERT_THRESHOLD   = 0.03;

    public function analyse(string $text): LinguisticFactor
    {
        // Split on sentence boundaries (keeping the delimiter to check for ?)
        $sentences = preg_split('/(?<=[.!?])\s+/u', trim($text), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $sentences = array_values(array_filter(
            $sentences,
            static fn(string $s): bool => str_word_count(trim($s)) > 0,
        ));

        if (count($sentences) < self::MIN_SENTENCES) {
            return new LinguisticFactor(
                confidence: 'HIGH',
                label: 'Question Frequency',
                rawValue: 0.0,
                displayValue: 'Not enough sentences',
                status: 'insufficient',
                explanation: sprintf('At least %d sentences are required.', self::MIN_SENTENCES),
            );
        }

        $questionCount = 0;
        foreach ($sentences as $sentence) {
            if (str_contains(trim($sentence), '?')) {
                $questionCount++;
            }
        }

        $ratio = $questionCount / count($sentences);

        $status = match (true) {
            $ratio >= self::HUMAN_THRESHOLD => 'human_signal',
            $ratio >= self::ALERT_THRESHOLD => 'normal',
            default                         => 'alert',
        };

        $explanation = sprintf(
            '%d of %d sentences (%.0f%%) are questions. ',
            $questionCount,
            count($sentences),
            $ratio * 100,
        );

        $explanation .= match ($status) {
            'human_signal' => 'Frequent rhetorical questions are characteristic of natural human writing (Desaire et al., 2023).',
            'normal'       => 'Question frequency is within the normal range.',
            default        => 'Very few or no questions — AI text in formal register rarely uses rhetorical questions (Desaire et al., 2023).',
        };

        return new LinguisticFactor(
            confidence: 'HIGH',
            label: 'Question Frequency',
            rawValue: $ratio * 100,
            displayValue: sprintf('%.0f%%', $ratio * 100),
            status: $status,
            explanation: $explanation,
        );
    }
}
