<?php

declare(strict_types=1);

namespace Tests\Unit\Linguistic;

use App\Services\AiDetector\Linguistic\SentenceLengthAnalyser;
use PHPUnit\Framework\TestCase;

final class SentenceLengthAnalyserTest extends TestCase
{
    private SentenceLengthAnalyser $analyser;

    protected function setUp(): void
    {
        $this->analyser = new SentenceLengthAnalyser();
    }

    public function testUniformSentenceLengthIsAlert(): void
    {
        // Build 6 sentences of exactly 10 words each → mean = 10, stddev ≈ 0, CV ≈ 0.
        // CV < 0.35 → status = 'alert'.
        $sentence = 'The dog ran quickly down the long empty street today.'; // 10 words
        $text     = implode(' ', array_fill(0, 6, $sentence));

        $result = $this->analyser->analyse($text);

        $this->assertSame('alert', $result->status, 'Perfectly uniform sentence lengths (CV near 0) should return status "alert".');
        $this->assertLessThan(0.35, $result->rawValue, 'The CV for uniform sentence lengths should be below 0.35.');
    }

    public function testHighlyVariedLengthIsNormal(): void
    {
        // Mix very short (2-word) and very long (25-word) sentences so CV > 0.45 → 'normal'.
        // Short: ~2 words. Long: ~25 words.
        // We use enough sentences to pass the 50-word total gate.
        $short1  = 'Stop. ';
        $short2  = 'Wait. ';
        $short3  = 'Look. ';
        $long1   = 'The extraordinarily complex international telecommunications bureaucracy '
            . 'systematically processes an enormous volume of multidisciplinary regulatory '
            . 'documentation every single calendar year. ';
        $long2   = 'Researchers collaboratively studying the disproportionate socioeconomic '
            . 'ramifications of unregulated artificial intelligence deployment have repeatedly '
            . 'concluded that urgent legislative intervention remains necessary. ';
        $long3   = 'Contemporary organisations navigating increasingly sophisticated global '
            . 'supply chains must continuously adapt their operational strategies to accommodate '
            . 'unprecedented logistical and regulatory challenges. ';

        $text = $short1 . $short2 . $short3 . $long1 . $long2 . $long3;

        $result = $this->analyser->analyse($text);

        $this->assertSame('normal', $result->status, 'A mix of very short and very long sentences (high CV) should return status "normal".');
        $this->assertGreaterThanOrEqual(0.45, $result->rawValue, 'The CV for highly varied sentence lengths should be 0.45 or above.');
    }

    public function testInsufficientTextReturnsInsufficient(): void
    {
        // Only 8 words — well below the 50-word minimum.
        $result = $this->analyser->analyse('This is a very short text sample.');

        $this->assertSame('insufficient', $result->status, 'Text under 50 words should return status "insufficient".');
    }

    public function testLabelIsCorrect(): void
    {
        $result = $this->analyser->analyse('Short text.');

        $this->assertSame('Sentence Length Variation (CV)', $result->label, 'The label must be "Sentence Length Variation (CV)".');
    }
}
