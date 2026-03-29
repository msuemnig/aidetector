<?php

declare(strict_types=1);

namespace App\Services\AiDetector\DTOs;

/**
 * The full result of an analysis run, passed as Inertia props to the Results page.
 */
final readonly class AnalysisReport
{
    /**
     * @param string             $text                  Original submitted text.
     * @param int                $wordCount
     * @param int                $characterCount
     * @param float              $averageWordLength
     * @param string             $analysedAt            ISO 8601 timestamp.
     * @param int                $totalScore            0–100.
     * @param string             $classification        "Likely Human-Written" | "Possibly AI-Generated" | "Likely AI-Generated"
     * @param string             $classificationColour  "green" | "yellow" | "red"
     * @param DetectorResult[]   $detectorResults
     * @param LinguisticFactor[] $linguisticFactors
     * @param array<array{start:int,end:int,category:string,colour:string}> $highlights
     */
    public function __construct(
        public readonly string $text,
        public readonly int $wordCount,
        public readonly int $characterCount,
        public readonly float $averageWordLength,
        public readonly string $analysedAt,
        public readonly int $totalScore,
        public readonly string $classification,
        public readonly string $classificationColour,
        public readonly array $detectorResults,
        public readonly array $linguisticFactors,
        public readonly array $highlights,
    ) {}
}
