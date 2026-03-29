<?php

declare(strict_types=1);

namespace Tests\Unit\Detectors;

use App\Services\AiDetector\Detectors\AiVocabularyDetector;
use PHPUnit\Framework\TestCase;

final class AiVocabularyDetectorTest extends TestCase
{
    private AiVocabularyDetector $detector;

    protected function setUp(): void
    {
        $this->detector = new AiVocabularyDetector();
    }

    public function testNoMatchesReturnsZeroScore(): void
    {
        $result = $this->detector->analyse('The cat sat on the mat and looked out the window.');

        $this->assertSame(0, $result->score, 'Score should be 0 when no AI vocab terms are present.');
        $this->assertSame(0, $result->occurrences, 'Occurrences should be 0 when no AI vocab terms are present.');
    }

    public function testSingleTermMatches(): void
    {
        $result = $this->detector->analyse('The system needs a robust solution.');

        $this->assertSame(2, $result->score, 'A single matched term should award 2 points.');
        $this->assertSame(1, $result->occurrences, 'One distinct term should be counted as one occurrence.');
        $this->assertSame(['robust'], $result->matches, 'The matched term array should contain "robust".');
    }

    public function testMultiWordPhraseMatches(): void
    {
        $result = $this->detector->analyse('We should delve into the subject more deeply.');

        $this->assertContains('delve into', $result->matches, '"delve into" should be in the matches array.');
    }

    public function testScoreCapAt15(): void
    {
        // Use 8+ distinct terms: robust, holistic, paradigm, ecosystem, leverage,
        // streamline, synergy, nuanced — that would give 8 × 2 = 16, capped to 15.
        $text = 'We need a robust, holistic paradigm for the ecosystem. '
            . 'We can leverage this to streamline the synergy and ensure a nuanced approach.';

        $result = $this->detector->analyse($text);

        $this->assertLessThanOrEqual(15, $result->score, 'Score must never exceed the cap of 15.');
    }

    public function testCaseInsensitive(): void
    {
        $result = $this->detector->analyse('The system requires a ROBUST architecture.');

        $this->assertContains('robust', $result->matches, 'Matching should be case-insensitive; "ROBUST" should match "robust".');
        $this->assertGreaterThan(0, $result->score, 'Score should be greater than 0 when a term matches case-insensitively.');
    }

    public function testWholeWordOnly(): void
    {
        // "robustness" contains "robust" as a substring but must NOT match.
        $result = $this->detector->analyse('The robustness of the system was impressive.');

        $this->assertNotContains('robust', $result->matches, '"robust" should not match inside the word "robustness".');
        $this->assertSame(0, $result->score, 'Score should be 0 when "robust" only appears as a substring of another word.');
    }

    public function testReturnsCorrectCategory(): void
    {
        $result = $this->detector->analyse('Some ordinary text without special vocabulary.');

        $this->assertSame('AI Vocabulary', $result->category, 'The category must be "AI Vocabulary".');
    }

    public function testCapIs15(): void
    {
        $result = $this->detector->analyse('Some ordinary text.');

        $this->assertSame(15, $result->cap, 'The cap property must always be 15.');
    }
}
