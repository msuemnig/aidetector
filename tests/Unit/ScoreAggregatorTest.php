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
        $score = $this->aggregator->aggregate(
            [$this->makeResult('A', 10, 10, 'HIGH')],
            [],
        );

        $this->assertSame(10, $score, 'HIGH confidence: score should be multiplied by 1.0');
    }

    public function testMediumConfidenceReducesScore(): void
    {
        // 8 × 0.75 = 6.0 → 6
        $score = $this->aggregator->aggregate(
            [$this->makeResult('A', 8, 10, 'MEDIUM')],
            [],
        );

        $this->assertSame(6, $score, 'MEDIUM confidence: score should be multiplied by 0.75');
    }

    public function testLowConfidenceHalvesScore(): void
    {
        // 8 × 0.50 = 4.0 → 4
        $score = $this->aggregator->aggregate(
            [$this->makeResult('A', 8, 10, 'LOW')],
            [],
        );

        $this->assertSame(4, $score, 'LOW confidence: score should be multiplied by 0.50');
    }

    public function testMixedConfidencesAreWeightedCorrectly(): void
    {
        // HIGH 10 × 1.0 = 10, MEDIUM 8 × 0.75 = 6, LOW 8 × 0.5 = 4 → total 20
        $score = $this->aggregator->aggregate(
            [
                $this->makeResult('A', 10, 15, 'HIGH'),
                $this->makeResult('B', 8,  10, 'MEDIUM'),
                $this->makeResult('C', 8,  8,  'LOW'),
            ],
            [],
        );

        $this->assertSame(20, $score, 'Mixed confidence scores should each be weighted independently');
    }

    public function testDetectorScoresAreSummedWithHighConfidence(): void
    {
        // 5 × 1.0 + 7 × 1.0 = 12
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
        // Sentence Length Variation (CV) is HIGH — alert = 8 pts (unmodified)
        $score = $this->aggregator->aggregate(
            [],
            [$this->makeFactor('Sentence Length Variation (CV)', 'alert')],
        );

        $this->assertSame(8, $score, 'Sentence Length Variation (CV) (HIGH) alert should contribute 8 pts');
    }

    public function testLinguisticHighConfidenceWarningAddsCorrectPoints(): void
    {
        // Sentence Length Variation (CV) warning = 4 pts
        $score = $this->aggregator->aggregate(
            [],
            [$this->makeFactor('Sentence Length Variation (CV)', 'warning')],
        );

        $this->assertSame(4, $score);
    }

    public function testLinguisticMediumConfidenceAlertAddsReducedPoints(): void
    {
        // Transition Word Rate is MEDIUM — base 8 × 0.75 = 6
        $score = $this->aggregator->aggregate(
            [],
            [$this->makeFactor('Transition Word Rate', 'alert')],
        );

        $this->assertSame(6, $score, 'Transition Word Rate (MEDIUM) alert should contribute 6 pts');
    }

    public function testLinguisticMediumConfidenceWarningAddsReducedPoints(): void
    {
        // Transition Word Rate warning = base 4 × 0.75 = 3
        $score = $this->aggregator->aggregate(
            [],
            [$this->makeFactor('Transition Word Rate', 'warning')],
        );

        $this->assertSame(3, $score, 'Transition Word Rate (MEDIUM) warning should contribute 3 pts');
    }

    public function testLinguisticPunctuationAlertAddsReducedPoints(): void
    {
        // Punctuation Pattern is MEDIUM — base 4 × 0.75 = 3
        $score = $this->aggregator->aggregate(
            [],
            [$this->makeFactor('Punctuation Pattern', 'alert')],
        );

        $this->assertSame(3, $score, 'Punctuation Pattern (MEDIUM) alert should contribute 3 pts');
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
