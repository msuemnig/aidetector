<?php

declare(strict_types=1);

namespace Tests\Unit\Linguistic;

use App\Services\AiDetector\Linguistic\LexicalDiversityAnalyser;
use PHPUnit\Framework\TestCase;

final class LexicalDiversityAnalyserTest extends TestCase
{
    private LexicalDiversityAnalyser $analyser;

    protected function setUp(): void
    {
        $this->analyser = new LexicalDiversityAnalyser();
    }

    public function testInsufficientTextUnder50Words(): void
    {
        // 10 unique words — well under the 50-word minimum.
        $text = 'The quick brown fox jumps over the lazy dog today.';

        $result = $this->analyser->analyse($text);

        $this->assertSame('insufficient', $result->status, 'Text under 50 words should return status "insufficient".');
    }

    public function testHighDiversityIsWarning(): void
    {
        // 100 words, all unique → TTR = 1.0, which is above the NORMAL_HIGH of 0.85 → 'warning'.
        $words = [];
        for ($i = 1; $i <= 100; $i++) {
            $words[] = 'uniquewordtoken' . $i;
        }
        $text = implode(' ', $words) . '.';

        $result = $this->analyser->analyse($text);

        $this->assertSame('warning', $result->status, 'A TTR of 1.0 (all unique words) is above 0.85 and should return status "warning".');
    }

    public function testLowDiversityIsWarning(): void
    {
        // 25 distinct words, each repeated 4 times → 100 total tokens, TTR = 0.25 → below 0.60 → 'warning'.
        $baseWords = [
            'apple', 'banana', 'cherry', 'delta', 'echo',
            'foxtrot', 'golf', 'hotel', 'india', 'juliet',
            'kilo', 'lima', 'mango', 'november', 'oscar',
            'papa', 'quebec', 'romeo', 'sierra', 'tango',
            'uniform', 'victor', 'whiskey', 'xray', 'yankee',
        ];
        // Repeat each word 4 times → 100 total words, 25 unique → TTR = 0.25.
        $words = array_merge($baseWords, $baseWords, $baseWords, $baseWords);
        $text  = implode(' ', $words) . '.';

        $result = $this->analyser->analyse($text);

        $this->assertSame('warning', $result->status, 'A TTR of 0.25 (below 0.60) should return status "warning".');
    }

    public function testNormalRangeIsNormal(): void
    {
        // We need a TTR between 0.60 and 0.85.
        // Strategy: 70 unique words + 30 repeated words from that set = 100 total.
        // TTR = 70 / 100 = 0.70 → within [0.60, 0.85] → 'normal'.
        $uniqueWords = [];
        for ($i = 1; $i <= 70; $i++) {
            $uniqueWords[] = 'wordtoken' . $i;
        }
        // Take the first 30 unique words and repeat them once.
        $repeatedWords = array_slice($uniqueWords, 0, 30);
        $allWords      = array_merge($uniqueWords, $repeatedWords);
        $text          = implode(' ', $allWords) . '.';

        $result = $this->analyser->analyse($text);

        $this->assertSame('normal', $result->status, 'A TTR of 0.70 (within the 0.60–0.85 normal range) should return status "normal".');
    }

    public function testDisplayValueIsPercentage(): void
    {
        // Build a 100-word text with TTR in the normal range to avoid an 'insufficient' result.
        $uniqueWords = [];
        for ($i = 1; $i <= 70; $i++) {
            $uniqueWords[] = 'wordtoken' . $i;
        }
        $repeatedWords = array_slice($uniqueWords, 0, 30);
        $text          = implode(' ', array_merge($uniqueWords, $repeatedWords)) . '.';

        $result = $this->analyser->analyse($text);

        $this->assertStringContainsString('%', $result->displayValue, 'The displayValue for a valid TTR result should contain a percentage symbol.');
    }

    public function testLabelIsCorrect(): void
    {
        $result = $this->analyser->analyse('word ');

        $this->assertSame('Lexical Diversity (TTR)', $result->label, 'The label must be "Lexical Diversity (TTR)".');
    }
}
