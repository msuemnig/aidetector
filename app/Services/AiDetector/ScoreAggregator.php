<?php

declare(strict_types=1);

namespace App\Services\AiDetector;

use App\Services\AiDetector\DTOs\DetectorResult;
use App\Services\AiDetector\DTOs\LinguisticFactor;

/**
 * Aggregates scores from detectors and linguistic analysers into a final 0–100 score.
 *
 * Detector scores are multiplied by a confidence weight before summing, so that
 * LOW-confidence heuristics (weak empirical backing) contribute less to the final
 * verdict than HIGH-confidence ones.
 *
 * Linguistic factor weights are pre-multiplied by their respective confidence levels:
 *   HIGH  × 1.00 — multiple independent empirical studies
 *   MEDIUM × 0.75 — practitioner/community evidence or single study
 */
final class ScoreAggregator
{
    /**
     * Multipliers applied to each detector's score based on evidence quality.
     */
    private const array CONFIDENCE_WEIGHTS = [
        'HIGH'   => 1.00,
        'MEDIUM' => 0.75,
        'LOW'    => 0.50,
    ];

    /**
     * Linguistic factor point contributions for AI signals (positive = more AI-like).
     * Keys must match the $label returned by each linguistic analyser.
     *
     * DEPRECATED (zeroed-out) heuristics — these fire on both human and AI text equally
     * and provide no discriminating signal. Identified via accuracy analysis on 72 fixtures
     * across 3 genres (academic, fiction, opinion). They remain in the analysis pipeline
     * for display/transparency but contribute zero points to the score:
     *   - Rare Word Rate:         alert fired on 72/72 samples (100% human AND AI)
     *   - Zipf's Law Fit (R²):   alert fired on 72/72 samples (100% human AND AI)
     *   - Lexical Diversity (TTR): warning fired on ~86% of all samples regardless of source
     */
    private const array LINGUISTIC_WEIGHTS = [
        // --- Active differentiators ---
        // Sentence CV: alert fires on ~56% of AI vs ~11% of human — strong differentiator
        'Sentence Length Variation (CV)' => ['alert' => 10, 'warning' => 5],
        // Passive Voice: low passive = AI signal (Reinhart et al., PNAS 2025)
        'Passive Voice Rate'             => ['alert' => 8,  'warning' => 4],
        // Flesch-Kincaid: fires on ~78% AI vs ~56% human — moderate differentiator
        'Flesch-Kincaid Grade Level'     => ['alert' => 6,  'warning' => 3],
        // Transition Word Rate: overuse is AI signal
        'Transition Word Rate'           => ['alert' => 8,  'warning' => 4],
        // Punctuation: em-dash/semicolon overuse
        'Punctuation Pattern'            => ['alert' => 4,  'warning' => 2],
        // Paragraph uniformity: low CV fires on ~65% AI vs ~39% human
        'Paragraph Length Variance'      => ['alert' => 6,  'warning' => 3],
        // Function word density: low density fires on ~65% AI vs ~28% human
        'Function Word Density'          => ['alert' => 6,  'warning' => 3],

        // --- Deprecated: zero-weighted (fire on everything, no discrimination) ---
        'Lexical Diversity (TTR)'        => ['alert' => 0,  'warning' => 0],
        'Rare Word Rate'                 => ['alert' => 0,  'warning' => 0],
        "Zipf's Law Fit (R²)"           => ['alert' => 0,  'warning' => 0],
    ];

    /**
     * Linguistic factor point deductions for human signals (subtracted from score).
     * Keyed by label, value is how many points to subtract when 'human_signal' status.
     *
     * DEPRECATED: Hapax Legomenon Rate — fires as human_signal on ~97% of ALL samples
     * including AI text. Provides no discrimination and has been removed.
     */
    private const array HUMAN_SIGNAL_WEIGHTS = [
        // Sentence CV >= 0.65: fires on ~33% human vs ~6% AI — strong human signal
        'Sentence Length Variation (CV)' => 8,
        // Paragraph variance: high CV fires on ~33% human vs ~6% AI
        'Paragraph Length Variance'      => 7,
        // Function word density > 0.50: fires on ~22% human vs ~4% AI
        'Function Word Density'          => 6,
        // Epistemic markers: fires on ~39% human vs ~13% AI — largest effect size (Herbold 2023)
        'Epistemic Markers'              => 7,
        // Contractions: fires on ~61% human vs ~28% AI
        'Contraction Usage'              => 5,

        // --- Deprecated: fires on AI too, no discrimination ---
        // 'Hapax Legomenon Rate' => 0,
    ];

    /**
     * Detector Breadth Bonus — adjusts score based on how many independent
     * pattern detectors fire (score > 0) on the same text.
     *
     * Empirical basis (72-fixture accuracy study, March 2026):
     *   0–1 detectors fire: 67% human, 33% AI → human signal (subtract)
     *   2 detectors fire:   28% human, 28% AI → neutral
     *   3 detectors fire:    6% human, 39% AI → AI signal (add)
     *   4+ detectors fire:   0% human, 11% AI → strong AI signal (add)
     *
     * When multiple independent heuristics converge on the same text, the
     * probability of AI authorship increases multiplicatively. Conversely,
     * when very few fire, it suggests the text lacks the structural
     * regularities that characterise AI output.
     */
    private const array BREADTH_BONUS = [
        0 => -10,   // 0 detectors: strong human signal
        1 => -6,    // 1 detector: moderate human signal
        2 => 0,     // 2 detectors: neutral
        3 => 10,    // 3 detectors: moderate AI signal (6% human vs 39% AI)
        4 => 15,    // 4+ detectors: strong AI signal (0% human in dataset)
    ];

    /**
     * @param DetectorResult[]   $detectorResults
     * @param LinguisticFactor[] $linguisticFactors
     */
    public function aggregate(array $detectorResults, array $linguisticFactors): int
    {
        $raw = 0.0;

        foreach ($detectorResults as $result) {
            $weight = self::CONFIDENCE_WEIGHTS[$result->confidence] ?? 1.0;
            // Negative scores indicate human signals — apply weight but keep sign
            $raw += $result->score * $weight;
        }

        foreach ($linguisticFactors as $factor) {
            if ($factor->status === 'insufficient') {
                continue;
            }

            // Human signal — subtract points
            if ($factor->status === 'human_signal') {
                $raw -= self::HUMAN_SIGNAL_WEIGHTS[$factor->label] ?? 0;
                continue;
            }

            // AI signal — add points
            $weights = self::LINGUISTIC_WEIGHTS[$factor->label] ?? null;
            if ($weights === null) {
                continue;
            }

            $raw += match ($factor->status) {
                'alert'   => $weights['alert'],
                'warning' => $weights['warning'],
                default   => 0,
            };
        }

        // --- Detector Breadth Bonus ---
        // Count how many independent positive-score pattern detectors fired.
        $firedCount = 0;
        foreach ($detectorResults as $result) {
            if ($result->score > 0) {
                $firedCount++;
            }
        }

        $breadthKey = min($firedCount, 4); // 4+ all use the same bonus
        $raw += self::BREADTH_BONUS[$breadthKey];

        return max(0, min(100, (int) round($raw)));
    }

    public function classify(int $score): string
    {
        return match (true) {
            $score < 30 => 'Likely Human-Written',
            $score < 60 => 'Possibly AI-Generated',
            default     => 'Likely AI-Generated',
        };
    }

    public function classificationColour(int $score): string
    {
        return match (true) {
            $score < 30 => 'green',
            $score < 60 => 'yellow',
            default     => 'red',
        };
    }
}
