<?php

declare(strict_types=1);

namespace App\Services\AiDetector\DTOs;

/**
 * Represents the result of a single linguistic analysis pass.
 *
 * Each analyser returns one of these rather than a scored detector result,
 * as linguistic factors provide contextual signals rather than direct scores.
 */
final readonly class LinguisticFactor
{
    /**
     * @param string $label        Human-readable name for this factor.
     * @param float  $rawValue     The underlying numeric measurement (e.g. a ratio or grade).
     * @param string $displayValue A formatted string for display (e.g. "72.4%" or "Grade 13.2").
     * @param string $status       One of: 'normal' | 'warning' | 'alert' | 'insufficient'.
     * @param string $explanation  A plain-English explanation of what the value indicates.
     * @param string $confidence   Evidence quality: 'HIGH' | 'MEDIUM' | 'LOW'.
     */
    public function __construct(
        public readonly string $label,
        public readonly float $rawValue,
        public readonly string $displayValue,
        public readonly string $status,
        public readonly string $explanation,
        public readonly string $confidence = 'HIGH',
    ) {}
}
