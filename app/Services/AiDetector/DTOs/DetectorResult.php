<?php

declare(strict_types=1);

namespace App\Services\AiDetector\DTOs;

/**
 * The output of a single pattern detector.
 */
final readonly class DetectorResult
{
    /**
     * @param string   $category    Display name for this detector category.
     * @param int      $score       Points contributed (0 to $cap).
     * @param int      $cap         Maximum points this detector can contribute.
     * @param int      $occurrences How many times the pattern was found.
     * @param string[] $matches     The actual matched strings or phrases.
     * @param string   $explanation Human-readable summary of what was found.
     * @param string   $confidence  Evidence quality: 'HIGH' | 'MEDIUM' | 'LOW'.
     */
    public function __construct(
        public readonly string $category,
        public readonly int $score,
        public readonly int $cap,
        public readonly int $occurrences,
        public readonly array $matches,
        public readonly string $explanation,
        public readonly string $confidence,
    ) {}
}
