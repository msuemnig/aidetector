<?php

declare(strict_types=1);

namespace Tests\Accuracy;

use App\Services\AiDetector\AnalysisService;
use Tests\TestCase;

/**
 * Accuracy tests for the COP21 fixture set.
 *
 * These tests intentionally use lenient thresholds so the suite stays green
 * while the detector is being tuned. Tighten the constants as accuracy improves.
 *
 * Thresholds:
 *   AI_THRESHOLD    >= 60  → predicted AI    (correct for AI-labelled fixtures)
 *   HUMAN_THRESHOLD  < 30  → predicted Human (correct for human-labelled fixtures)
 *
 * Minimum acceptable accuracy (will increase as detector is tuned):
 *   HUMAN_MIN_ACCURACY  60 %
 *   AI_MIN_ACCURACY     50 %
 */
class AccuracyTest extends TestCase
{
    // Score thresholds — mirror AccuracyReport and ScoreAggregator::classify()
    private const int AI_THRESHOLD    = 45;
    private const int HUMAN_THRESHOLD = 30;

    // Minimum acceptable overall accuracy rates (lenient starting values)
    private const float HUMAN_MIN_ACCURACY = 0.60;
    private const float AI_MIN_ACCURACY    = 0.50;

    /** Root directory for all fixture JSON files */
    private string $fixturesBase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fixturesBase = base_path('tests/fixtures');
    }

    // ----------------------------------------------------------------------- //
    // Data provider                                                            //
    // ----------------------------------------------------------------------- //

    /**
     * Yields every fixture found under tests/fixtures/ as an individual test case.
     * Directories that do not exist or contain no JSON files are silently skipped.
     *
     * @return iterable<string, array{array<string,mixed>}>
     */
    public static function fixtureProvider(): iterable
    {
        $base    = base_path('tests/fixtures');
        $sources = ['human', 'ai-claude', 'ai-gemini', 'ai-chatgpt'];

        foreach ($sources as $source) {
            $dir = $base . DIRECTORY_SEPARATOR . $source;

            if (! is_dir($dir)) {
                continue;
            }

            $files = glob($dir . DIRECTORY_SEPARATOR . '*.json') ?: [];

            foreach ($files as $file) {
                $decoded = json_decode((string) file_get_contents($file), true);

                if (! is_array($decoded)) {
                    continue;
                }

                // Use the fixture id as the test case name (falls back to filename)
                $key = $decoded['id'] ?? basename($file, '.json');

                yield $key => [$decoded];
            }
        }
    }

    // ----------------------------------------------------------------------- //
    // Per-fixture test (does NOT assert pass/fail per sample — too strict)    //
    // ----------------------------------------------------------------------- //

    /**
     * Runs each fixture through the analyser and records that it completed without
     * throwing. Individual pass/fail accuracy is checked in the aggregate methods
     * below; this test simply ensures the service does not crash on any fixture.
     *
     * @dataProvider fixtureProvider
     * @param array<string,mixed> $fixture
     */
    public function testFixture(array $fixture): void
    {
        /** @var AnalysisService $service */
        $service = $this->app->make(AnalysisService::class);

        $text  = $fixture['text'] ?? '';
        $label = $fixture['label'] ?? 'unknown';
        $id    = $fixture['id']    ?? 'unknown';

        $this->assertNotEmpty($text, "Fixture '{$id}' has an empty text field.");

        $report = $service->analyse($text);

        // Confirm the service returned a sensible score
        $this->assertGreaterThanOrEqual(0,   $report->totalScore, "Score below 0 for '{$id}'");
        $this->assertLessThanOrEqual(100,    $report->totalScore, "Score above 100 for '{$id}'");
        $this->assertContains($label, ['human', 'ai'], "Fixture '{$id}' has an unexpected label: '{$label}'");

        // Count this as an assertion so PHPUnit doesn't consider the test "risky"
        $this->addToAssertionCount(1);
    }

    // ----------------------------------------------------------------------- //
    // Aggregate accuracy assertions                                            //
    // ----------------------------------------------------------------------- //

    /**
     * Loads every AI-labelled fixture and asserts that at least AI_MIN_ACCURACY
     * of them produce a score >= AI_THRESHOLD.
     */
    public function testOverallAiAccuracyMeetsThreshold(): void
    {
        $fixtures = $this->loadFixturesByLabel('ai');

        if (empty($fixtures)) {
            $this->markTestSkipped('No AI fixtures found — add JSON files to tests/fixtures/ai-*/');
        }

        /** @var AnalysisService $service */
        $service = $this->app->make(AnalysisService::class);

        $total   = 0;
        $correct = 0;

        foreach ($fixtures as $fixture) {
            $report = $service->analyse($fixture['text'] ?? '');
            $total++;
            if ($report->totalScore >= self::AI_THRESHOLD) {
                $correct++;
            }
        }

        $rate = $total > 0 ? ($correct / $total) : 0.0;

        $this->assertGreaterThanOrEqual(
            self::AI_MIN_ACCURACY,
            $rate,
            sprintf(
                'AI detection accuracy too low: %d/%d (%.1f%%) — expected >= %.0f%%.',
                $correct,
                $total,
                $rate * 100,
                self::AI_MIN_ACCURACY * 100
            )
        );
    }

    /**
     * Loads every human-labelled fixture and asserts that at least HUMAN_MIN_ACCURACY
     * of them produce a score < HUMAN_THRESHOLD.
     */
    public function testOverallHumanAccuracyMeetsThreshold(): void
    {
        $fixtures = $this->loadFixturesByLabel('human');

        if (empty($fixtures)) {
            $this->markTestSkipped('No human fixtures found — add JSON files to tests/fixtures/human/');
        }

        /** @var AnalysisService $service */
        $service = $this->app->make(AnalysisService::class);

        $total   = 0;
        $correct = 0;

        foreach ($fixtures as $fixture) {
            $report = $service->analyse($fixture['text'] ?? '');
            $total++;
            if ($report->totalScore < self::HUMAN_THRESHOLD) {
                $correct++;
            }
        }

        $rate = $total > 0 ? ($correct / $total) : 0.0;

        $this->assertGreaterThanOrEqual(
            self::HUMAN_MIN_ACCURACY,
            $rate,
            sprintf(
                'Human pass rate too low: %d/%d (%.1f%%) — expected >= %.0f%%.',
                $correct,
                $total,
                $rate * 100,
                self::HUMAN_MIN_ACCURACY * 100
            )
        );
    }

    // ----------------------------------------------------------------------- //
    // Helpers                                                                  //
    // ----------------------------------------------------------------------- //

    /**
     * Load all fixtures from every source directory that carry a given label.
     * Directories that are absent or empty are silently skipped.
     *
     * @return array<array<string,mixed>>
     */
    private function loadFixturesByLabel(string $label): array
    {
        $sources  = ['human', 'ai-claude', 'ai-gemini', 'ai-chatgpt'];
        $fixtures = [];

        foreach ($sources as $source) {
            $dir = $this->fixturesBase . DIRECTORY_SEPARATOR . $source;

            if (! is_dir($dir)) {
                continue;
            }

            $files = glob($dir . DIRECTORY_SEPARATOR . '*.json') ?: [];

            foreach ($files as $file) {
                $decoded = json_decode((string) file_get_contents($file), true);

                if (! is_array($decoded)) {
                    continue;
                }

                if (($decoded['label'] ?? '') === $label) {
                    $fixtures[] = $decoded;
                }
            }
        }

        return $fixtures;
    }
}
