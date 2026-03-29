<?php

declare(strict_types=1);

namespace Tests\Unit\Detectors;

use App\Services\AiDetector\Detectors\ElegantVariationDetector;
use PHPUnit\Framework\TestCase;

final class ElegantVariationDetectorTest extends TestCase
{
    private ElegantVariationDetector $detector;

    protected function setUp(): void
    {
        $this->detector = new ElegantVariationDetector();
    }

    public function testNoVariationReturnsZeroScore(): void
    {
        $result = $this->detector->analyse('The cat sat on the mat and looked out the window today.');

        $this->assertSame(0, $result->score, 'Score should be 0 when no synonym clusters are triggered.');
        $this->assertSame(0, $result->occurrences, 'Occurrences should be 0 when no clusters are triggered.');
    }

    public function testTwoMembersOfClusterDoesNotTrigger(): void
    {
        // "company" and "firm" are both in the 'company' cluster, but only 2 members — needs 3 to trigger.
        $result = $this->detector->analyse('The company hired a new manager. The firm expanded its operations.');

        $this->assertSame(0, $result->score, 'Two cluster members should not trigger a score (minimum is 3).');
    }

    public function testThreeMembersOfClusterTriggers(): void
    {
        // "company", "firm", and "enterprise" are all in the 'company' cluster → 3 members → triggers.
        $result = $this->detector->analyse(
            'The company grew rapidly. The firm hired new staff. The enterprise expanded globally.'
        );

        $this->assertSame(4, $result->score, 'Three members of one cluster should award exactly 4 points.');
        $this->assertSame(1, $result->occurrences, 'One triggered cluster should count as one occurrence.');
    }

    public function testMultipleClustersCanTrigger(): void
    {
        // Trigger 'use' cluster: use, utilise, employ (3 members).
        // Trigger 'help' cluster: help, assist, support (3 members).
        // 2 clusters × 4 pts = 8, which is also the cap.
        $text = 'We use the tool to help others. '
            . 'We can utilise this resource to assist the team. '
            . 'We should employ best methods to support everyone.';

        $result = $this->detector->analyse($text);

        $this->assertSame(8, $result->score, 'Two triggered clusters should award 8 points (2 × 4), which equals the cap.');
        $this->assertSame(2, $result->occurrences, 'Two distinct clusters should be counted as two occurrences.');
    }

    public function testCaseInsensitive(): void
    {
        // Mixed case variants of 'company' cluster members: Company, FIRM, enterprise.
        $result = $this->detector->analyse(
            'The Company is growing. The FIRM is profitable. The enterprise is expanding.'
        );

        $this->assertSame(4, $result->score, 'Cluster matching should be case-insensitive.');
        $this->assertSame(1, $result->occurrences, 'One triggered cluster should be detected regardless of letter case.');
    }
}
