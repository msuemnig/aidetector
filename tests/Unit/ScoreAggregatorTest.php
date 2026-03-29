<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\AiDetector\DTOs\DetectorResult;
use App\Services\AiDetector\DTOs\LinguisticFactor;
use App\Services\AiDetector\ScoreAggregator;
use PHPUnit\Framework\TestCase;

final class ScoreAggregatorTest extends TestCase
{
    private ScoreAggregator $aggregator;

    protected function setUp(): void
    {
        $this->aggregator = new ScoreAggregator();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeResult(string $category, int $score, int $cap, string $confidence): DetectorResult
    {
        return new DetectorResult(
            category:    $category,
            score:       $score,
            cap:         $cap,
            occurrences: 1,
            matches:     [],
            explanation: 'test',
            confidence:  $confidence,
        );
    }

    private function makeFactor(string $label, string $status): LinguisticFactor
    {
        return new LinguisticFactor(
            label:        $label,
            rawValue:     0.5,
            displayValue: 'test',
            status:       $status,
            explanation:  'test',
        );
    }

    // -------------------------------------------------------------------------
    // Zero / empty
    // -------------------------------------------------------------------------

    public function testZeroScoreForEmptyResults(): void
    {
        $this->assertSame(0, $this->aggregator->aggregate([], []));
    }

    // -------------------------------------------------------------------------
    // Confidence weighting for detectors
    // -------------------------------------------------------------------------

    public function testHighConfidenceUsesFullScore(): void
    {
        // 10 × 1.0 = 10, breadth bonus for 1 detector = -6 → 4 (clamped to 4)
        $score = $this->aggregator->aggregate(
            [$this->makeResult('A', 10, 10, 'HIGH')],
            [],
        );

        $this->assertSame(4, $score, 'HIGH confidence: 10 pts - 6 breadth penalty for single detector');
    }

    public function testMediumConfidenceReducesScore(): void
    {
        // 8 × 0.75 = 6, breadth bonus for 1 detector = -6 → 0
        $score = $this->aggregator->aggregate(
            [$this->makeResult('A', 8, 10, 'MEDIUM')],
            [],
        );

        $this->assertSame(0, $score, 'MEDIUM confidence: 6 pts - 6 breadth penalty → clamped to 0');
    }

    public function testLowConfidenceHalvesScore(): void
    {
        // 8 × 0.50 = 4, breadth bonus for 1 detector = -6 → 0 (clamped)
        $score = $this->aggregator->aggregate(
            [$this->makeResult('A', 8, 10, 'LOW')],
            [],
        );

        $this->assertSame(0, $score, 'LOW confidence: 4 pts - 6 breadth penalty → clamped to 0');
    }

    public function testMixedConfidencesAreWeightedCorrectly(): void
    {
        // HIGH 10 × 1.0 = 10, MEDIUM 8 × 0.75 = 6, LOW 8 × 0.5 = 4
        // Raw = 20, breadth bonus for 3 detectors = +10 → 30
        $score = $this->aggregator->aggregate(
            [
                $this->makeResult('A', 10, 15, 'HIGH'),
                $this->makeResult('B', 8,  10, 'MEDIUM'),
                $this->makeResult('C', 8,  8,  'LOW'),
            ],
            [],
        );

        $this->assertSame(30, $score, 'Mixed confidence 20 pts + 10 breadth bonus for 3 detectors');
    }

    public function testDetectorScoresAreSummedWithHighConfidence(): void
    {
        // 5 × 1.0 + 7 × 1.0 = 12, breadth bonus for 2 detectors = 0 → 12
        $score = $this->aggregator->aggregate(
            [
                $this->makeResult('A', 5, 10, 'HIGH'),
                $this->makeResult('B', 7, 15, 'HIGH'),
            ],
            [],
        );

        $this->assertSame(12, $score);
    }

    // -------------------------------------------------------------------------
    // Linguistic factor contributions
    // -------------------------------------------------------------------------

    public function testLinguisticHighConfidenceAlertAddsCorrectPoints(): void
    {
        // Sentence CV alert = 10, breadth bonus for 0 detectors = -10 → 0
        // Test with a detector to isolate the linguistic contribution:
        // detector 20 pts + linguistic 10 pts + breadth(1 det) -6 = 24
        $score = $this->aggregator->aggregate(
            [$this->makeResult('A', 20, 20, 'HIGH')],
            [$this->makeFactor('Sentence Length Variation (CV)', 'alert')],
        );

        $this->assertSame(24, $score, 'Sentence CV alert adds 10 pts (20 + 10 - 6 breadth)');
    }

    public function testLinguisticHighConfidenceWarningAddsCorrectPoints(): void
    {
        // detector 20 pts + linguistic 5 pts + breadth(1 det) -6 = 19
        $score = $this->aggregator->aggregate(
            [$this->makeResult('A', 20, 20, 'HIGH')],
            [$this->makeFactor('Sentence Length Variation (CV)', 'warning')],
        );

        $this->assertSame(19, $score);
    }

    public function testLinguisticMediumConfidenceAlertAddsReducedPoints(): void
    {
        // detector 20 pts + linguistic 8 pts + breadth(1 det) -6 = 22
        $score = $this->aggregator->aggregate(
            [$this->makeResult('A', 20, 20, 'HIGH')],
            [$this->makeFactor('Transition Word Rate', 'alert')],
        );

        $this->assertSame(22, $score, 'Transition Word Rate alert adds 8 pts');
    }

    public function testLinguisticMediumConfidenceWarningAddsReducedPoints(): void
    {
        // detector 20 pts + linguistic 4 pts + breadth(1 det) -6 = 18
        $score = $this->aggregator->aggregate(
            [$this->makeResult('A', 20, 20, 'HIGH')],
            [$this->makeFactor('Transition Word Rate', 'warning')],
        );

        $this->assertSame(18, $score, 'Transition Word Rate warning adds 4 pts');
    }

    public function testLinguisticPunctuationAlertAddsReducedPoints(): void
    {
        // detector 20 pts + linguistic 4 pts + breadth(1 det) -6 = 18
        $score = $this->aggregator->aggregate(
            [$this->makeResult('A', 20, 20, 'HIGH')],
            [$this->makeFactor('Punctuation Pattern', 'alert')],
        );

        $this->assertSame(18, $score, 'Punctuation Pattern alert adds 4 pts');
    }

    public function testBreadthBonusForZeroDetectors(): void
    {
        // 0 detectors → -10 breadth penalty, clamped to 0
        $score = $this->aggregator->aggregate([], []);

        $this->assertSame(0, $score, 'Zero detectors: -10 breadth, clamped to 0');
    }

    public function testBreadthBonusForFourDetectors(): void
    {
        // 4 detectors × 2 pts each = 8, breadth bonus +15 = 23
        $score = $this->aggregator->aggregate(
            [
                $this->makeResult('A', 2, 10, 'HIGH'),
                $this->makeResult('B', 2, 10, 'HIGH'),
                $this->makeResult('C', 2, 10, 'HIGH'),
                $this->makeResult('D', 2, 10, 'HIGH'),
            ],
            [],
        );

        $this->assertSame(23, $score, '4 detectors: 8 pts + 15 breadth bonus');
    }

    public function testDeprecatedHeuristicContributesZero(): void
    {
        // Rare Word Rate is deprecated — alert should contribute 0 pts
        $score = $this->aggregator->aggregate(
            [],
            [$this->makeFactor('Rare Word Rate', 'alert')],
        );

        $this->assertSame(0, $score, 'Deprecated Rare Word Rate should contribute 0 pts');
    }

    public function testInsufficientLinguisticStatusContributesZero(): void
    {
        $score = $this->aggregator->aggregate(
            [],
            [$this->makeFactor('Sentence Length Variation (CV)', 'insufficient')],
        );

        $this->assertSame(0, $score, '"insufficient" status should contribute 0 pts');
    }

    public function testUnknownLinguisticLabelContributesZero(): void
    {
        $score = $this->aggregator->aggregate(
            [],
            [$this->makeFactor('Unknown Factor', 'alert')],
        );

        $this->assertSame(0, $score, 'An unrecognised label should contribute 0 pts');
    }

    // -------------------------------------------------------------------------
    // Clamping
    // -------------------------------------------------------------------------

    public function testScoreClampedAt100(): void
    {
        // 60 × 1.0 + 60 × 1.0 = 120 → clamped to 100
        $score = $this->aggregator->aggregate(
            [
                $this->makeResult('A', 60, 60, 'HIGH'),
                $this->makeResult('B', 60, 60, 'HIGH'),
            ],
            [],
        );

        $this->assertSame(100, $score, 'Score should be clamped to a maximum of 100');
    }

    // -------------------------------------------------------------------------
    // classify()
    // -------------------------------------------------------------------------

    public function testClassifyHumanWritten(): void
    {
        $this->assertSame('Likely Human-Written', $this->aggregator->classify(25));
    }

    public function testClassifyPossiblyAI(): void
    {
        $this->assertSame('Possibly AI-Generated', $this->aggregator->classify(45));
    }

    public function testClassifyLikelyAI(): void
    {
        $this->assertSame('Likely AI-Generated', $this->aggregator->classify(75));
    }

    // -------------------------------------------------------------------------
    // classificationColour()
    // -------------------------------------------------------------------------

    public function testColourGreen(): void
    {
        $this->assertSame('green', $this->aggregator->classificationColour(10));
    }

    public function testColourYellow(): void
    {
        $this->assertSame('yellow', $this->aggregator->classificationColour(40));
    }

    public function testColourRed(): void
    {
        $this->assertSame('red', $this->aggregator->classificationColour(70));
    }
}
