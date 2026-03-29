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
     */
    private const array LINGUISTIC_WEIGHTS = [
        // HIGH confidence (×1.00)
        'Sentence Length Variation (CV)' => ['alert' => 8,  'warning' => 4],
        'Passive Voice Rate'             => ['alert' => 6,  'warning' => 3],
        'Flesch-Kincaid Grade Level'     => ['alert' => 5,  'warning' => 2],
        'Lexical Diversity (TTR)'        => ['alert' => 5,  'warning' => 2],
        'Rare Word Rate'                 => ['alert' => 5,  'warning' => 2],
        "Zipf's Law Fit (R²)"           => ['alert' => 4,  'warning' => 2],
        // MEDIUM confidence (×0.75)
        'Transition Word Rate'           => ['alert' => 6,  'warning' => 3],
        'Punctuation Pattern'            => ['alert' => 3,  'warning' => 2],
    ];

    /**
     * Linguistic factor point deductions for human signals (subtracted from score).
     * Keyed by label, value is how many points to subtract when 'human_signal' status.
     */
    private const array HUMAN_SIGNAL_WEIGHTS = [
        'Sentence Length Variation (CV)' => 6,   // HIGH — high CV = human
        'Hapax Legomenon Rate'           => 5,   // HIGH — many unique-once words = human
        'Paragraph Length Variance'      => 5,   // HIGH — varied paragraphs = human
        'Function Word Density'          => 4,   // HIGH — high function word ratio = human
        'Epistemic Markers'              => 5,   // HIGH — hedging/uncertainty = human
        'Contraction Usage'              => 3,   // MEDIUM — contractions = human
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
