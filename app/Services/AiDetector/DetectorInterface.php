<?php

declare(strict_types=1);

namespace App\Services\AiDetector;

use App\Services\AiDetector\DTOs\DetectorResult;

/**
 * Contract for all pattern-based AI text detectors.
 */
interface DetectorInterface
{
    public function analyse(string $text): DetectorResult;
}
