<?php

declare(strict_types=1);

namespace App\Services\AiDetector\Linguistic;

use App\Services\AiDetector\DTOs\LinguisticFactor;

/**
 * Detects passive voice constructions in the provided text.
 *
 * Passive voice is identified by a form of "to be" followed within three words
 * by a past participle (words ending in -ed or -en, plus a list of common
 * irregular past participles).
 *
 * Contrary to the common assumption, empirical research (Reinhart et al., PNAS 2025)
 * finds that instruction-tuned LLMs use agentless passive voice at roughly HALF the
 * rate of human writers. An unusually LOW passive rate is therefore the AI signal,
 * not a high one.
 */
final class PassiveVoiceAnalyser
{
    private const int   MIN_WORDS      = 50;
    private const float ALERT_MAX      = 5.0;   // below this → alert (AI-low signal)
    private const float WARNING_MAX    = 10.0;  // below this → warning

    /**
     * Regex pattern matching "to be" forms followed (within 2 intervening words)
     * by a past participle (-ed or -en ending).
     * Irregular past participles are handled by the extended alternation below.
     */
    private const string PASSIVE_PATTERN =
        '/\b(is|are|was|were|be|been|being)\s+(?:\w+\s+){0,2}(\w+(?:ed|en))\b/i';

    /**
     * Common irregular past participles that do not end in -ed or -en but are
     * used in passive constructions.
     */
    private const array IRREGULAR_PARTICIPLES = [
        'written', 'taken', 'given', 'known', 'seen', 'made', 'found',
        'left', 'said', 'told', 'brought', 'built', 'bought', 'caught',
        'felt', 'heard', 'held', 'kept', 'led', 'met', 'paid', 'read',
        'run', 'sent', 'set', 'shown', 'sold', 'stood', 'thought', 'won',
    ];

    /**
     * Analyse the passive-voice rate in the provided text.
     */
    public function analyse(string $text): LinguisticFactor
    {
        if (str_word_count($text) < self::MIN_WORDS) {
            return new LinguisticFactor(
                label: 'Passive Voice Rate',
                rawValue: 0.0,
                displayValue: 'Not enough text',
                status: 'insufficient',
                explanation: 'At least 50 words are required to assess passive voice usage reliably.',
            );
        }

        $sentences       = $this->splitSentences($text);
        $totalSentences  = count($sentences);

        if ($totalSentences === 0) {
            return new LinguisticFactor(
                label: 'Passive Voice Rate',
                rawValue: 0.0,
                displayValue: 'Not enough text',
                status: 'insufficient',
                explanation: 'No complete sentences could be identified in the provided text.',
            );
        }

        $passiveCount = $this->countPassiveSentences($sentences);
        $rate         = ($passiveCount / $totalSentences) * 100;
        $status       = $this->determineStatus($rate);

        return new LinguisticFactor(
            label: 'Passive Voice Rate',
            rawValue: $rate,
            displayValue: number_format($rate, 1) . '%',
            status: $status,
            explanation: $this->buildExplanation($rate, $passiveCount, $totalSentences, $status),
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
     * Count sentences that contain at least one passive construction.
     *
     * Checks both the standard regex (-ed/-en endings) and the irregular
     * participle list.
     *
     * @param list<string> $sentences
     */
    private function countPassiveSentences(array $sentences): int
    {
        $irregularPattern = '/\b(is|are|was|were|be|been|being)\s+(?:\w+\s+){0,2}'
            . '(' . implode('|', self::IRREGULAR_PARTICIPLES) . ')\b/i';

        $count = 0;

        foreach ($sentences as $sentence) {
            if (
                preg_match(self::PASSIVE_PATTERN, $sentence)
                || preg_match($irregularPattern, $sentence)
            ) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Determine the status band for a given passive rate.
     * LOW passive voice is the AI signal (Reinhart et al., PNAS 2025).
     */
    private function determineStatus(float $rate): string
    {
        return match (true) {
            $rate <= self::ALERT_MAX   => 'alert',
            $rate <= self::WARNING_MAX => 'warning',
            default                    => 'normal',
        };
    }

    /**
     * Build a plain-English explanation of the passive voice result.
     */
    private function buildExplanation(float $rate, int $passiveCount, int $total, string $status): string
    {
        $base = sprintf(
            '%d of %d %s (%s%%) contain passive voice constructions.',
            $passiveCount,
            $total,
            $total === 1 ? 'sentence' : 'sentences',
            number_format($rate, 1),
        );

        return match ($status) {
            'normal'  => $base . ' Passive voice usage is at a rate typical of human writing.',
            'warning' => $base . ' Passive voice usage is below the typical human range (>10%). Research shows instruction-tuned AI models use passive voice at roughly half the human rate.',
            default   => $base . ' Very low passive voice usage is a signal associated with AI-generated text — empirical studies find modern LLMs use agentless passive voice at roughly half the rate of human writers (Reinhart et al., PNAS 2025).',
        };
    }
}
