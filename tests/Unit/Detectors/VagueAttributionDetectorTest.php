<?php

declare(strict_types=1);

namespace Tests\Unit\Detectors;

use App\Services\AiDetector\Detectors\VagueAttributionDetector;
use PHPUnit\Framework\TestCase;

final class VagueAttributionDetectorTest extends TestCase
{
    private VagueAttributionDetector $detector;

    protected function setUp(): void
    {
        $this->detector = new VagueAttributionDetector();
    }

    public function testVaguePhrasesDetected(): void
    {
        $result = $this->detector->analyse('Studies show that AI is dangerous to society.');

        $this->assertGreaterThan(0, $result->score, '"Studies show" is a vague attribution phrase and should produce a score above 0.');
    }

    public function testSpecificYearExcludesMatch(): void
    {
        // The sentence contains a four-digit year (2024), which disqualifies the vague phrase.
        $result = $this->detector->analyse('According to a 2024 study, AI is dangerous to society.');

        $this->assertSame(0, $result->score, 'A sentence with a specific year should not be flagged as vague attribution.');
    }

    public function testMultipleDistinctPhraseTypes(): void
    {
        // Two distinct phrase types: "studies show" and "experts agree".
        // 2 distinct types × 3 points = 6.
        $text = 'Studies show that the climate is changing. '
            . 'Experts agree that action is needed now.';

        $result = $this->detector->analyse($text);

        $this->assertSame(6, $result->score, 'Two distinct vague attribution phrase types should award 6 points (2 × 3).');
        $this->assertSame(2, $result->occurrences, 'Two distinct phrase types should be counted as two occurrences.');
    }

    public function testSamePhraseMultipleTimesCountsOnce(): void
    {
        // "experts agree" appears twice, but it is the same phrase type — counted only once.
        // 1 distinct type × 3 points = 3.
        $text = 'Experts agree that the policy is sound. '
            . 'Experts agree that changes are needed.';

        $result = $this->detector->analyse($text);

        $this->assertSame(3, $result->score, 'Repeating the same vague phrase type should count as only one distinct type (3 points).');
        $this->assertSame(1, $result->occurrences, 'The same phrase type appearing multiple times should be counted as one distinct occurrence.');
    }
}
