<?php

declare(strict_types=1);

namespace Tests\Unit\Linguistic;

use App\Services\AiDetector\Linguistic\FleschKincaidAnalyser;
use PHPUnit\Framework\TestCase;

final class FleschKincaidAnalyserTest extends TestCase
{
    private FleschKincaidAnalyser $analyser;

    protected function setUp(): void
    {
        $this->analyser = new FleschKincaidAnalyser();
    }

    public function testInsufficientTextReturnsInsufficient(): void
    {
        // Only 5 words — well below the 50-word minimum.
        $result = $this->analyser->analyse('This is very short text.');

        $this->assertSame('insufficient', $result->status, 'Text under 50 words should return status "insufficient".');
    }

    public function testHighGradeIsAlert(): void
    {
        // Construct a text of 50+ words using very long, multi-syllable words so that
        // the Average Syllables per Word (ASW) is high enough to push the FK grade above 14.
        //
        // FK formula: grade = 0.39 * ASL + 11.8 * ASW - 15.59
        // With ASL ≈ 25 and ASW ≈ 5.0 → grade ≈ 0.39*25 + 11.8*5.0 - 15.59 ≈ 9.75 + 59.0 - 15.59 ≈ 53.16
        //
        // Words are deliberately polysyllabic (bureaucratisation, institutionalise, etc.)
        // arranged into two long sentences of exactly 25 words each (50 total).
        $text = 'The extraordinarily sophisticated bureaucratisation of multidisciplinary '
            . 'telecommunications infrastructure characteristically demonstrates disproportionate '
            . 'incomprehensibility to unsophisticated constituencies throughout contemporary '
            . 'international regulatory environments exhibiting unprecedented organisational '
            . 'complexities globally here. '
            . 'Internationally intergovernmental organisations collaboratively institutionalise '
            . 'constitutionalisation processes characteristically disadvantaging underrepresented '
            . 'socioeconomic communities disproportionately whilst simultaneously perpetuating '
            . 'institutionalised inequalities across multidisciplinary telecommunications '
            . 'sectors worldwide today.';

        $result = $this->analyser->analyse($text);

        $this->assertSame('alert', $result->status, 'A text with very high average syllables per word should return status "alert" (FK grade > 14).');
        $this->assertGreaterThan(14.0, $result->rawValue, 'The raw FK grade value should exceed 14.0 for highly complex text.');
    }

    public function testLabelIsCorrect(): void
    {
        $result = $this->analyser->analyse('This is a test.');

        $this->assertSame('Flesch-Kincaid Grade Level', $result->label, 'The label must be "Flesch-Kincaid Grade Level".');
    }

    public function testDisplayValueContainsGrade(): void
    {
        // Use a long enough text (50+ words) to avoid 'insufficient', with moderate complexity.
        $text = 'The quick brown fox jumps over the lazy dog each morning outside. '
            . 'A simple sentence is easy to read and understand by everyone. '
            . 'Children learn to write short sentences first before attempting longer and more complex ones. '
            . 'Reading helps to build vocabulary and comprehension skills over time for all students.';

        $result = $this->analyser->analyse($text);

        $this->assertStringContainsString('Grade', $result->displayValue, 'The displayValue should start with "Grade" for a valid FK result.');
    }
}
