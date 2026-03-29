<?php

declare(strict_types=1);

namespace App\Services\AiDetector;

/**
 * Runs all JSON fixtures through AnalysisService and returns structured accuracy data.
 * Extracted from the AccuracyReport artisan command so it can be reused by controllers.
 */
final class AccuracyReportService
{
    private const int AI_THRESHOLD    = 45;
    private const int HUMAN_THRESHOLD = 30;

    private const array SOURCES = ['human', 'ai-claude', 'ai-gemini', 'ai-chatgpt'];

    public function __construct(
        private readonly AnalysisService $analysisService,
        private readonly ScoreAggregator $scoreAggregator,
    ) {}

    /**
     * @return array{rows: list<array>, samples: list<array>, summary: array, overall: array, thresholds: array, generatedAt: string}
     */
    public function generate(): array
    {
        $fixturesBase = base_path('tests/fixtures');
        $rows    = [];
        $samples = [];

        // Indices for curated sample picks: best human pass, highest AI, uncertain AI, lowest AI
        $bestHuman    = null;
        $highestAi    = null;
        $uncertainAi  = null;
        $lowestAi     = null;

        foreach (self::SOURCES as $source) {
            $dir = $fixturesBase . DIRECTORY_SEPARATOR . $source;
            if (! is_dir($dir)) {
                continue;
            }

            $files = glob($dir . DIRECTORY_SEPARATOR . '*.json') ?: [];

            foreach ($files as $file) {
                $decoded = json_decode((string) file_get_contents($file), true);
                if (! is_array($decoded)) {
                    continue;
                }

                $text  = $decoded['text'] ?? '';
                $label = $decoded['label'] ?? 'human';

                $report = $this->analysisService->analyse($text);
                $score  = $report->totalScore;
                $result = $this->classifyResult($score, $label);

                // Top 5 detectors by score
                $detectors = $report->detectorResults;
                usort($detectors, static fn ($a, $b): int => $b->score <=> $a->score);
                $topDetectors = [];
                foreach (array_slice($detectors, 0, 5) as $d) {
                    if ($d->score > 0) {
                        $topDetectors[] = [
                            'category'    => $d->category,
                            'score'       => $d->score,
                            'cap'         => $d->cap,
                            'occurrences' => $d->occurrences,
                            'confidence'  => $d->confidence,
                        ];
                    }
                }

                $row = [
                    'id'             => $decoded['id'] ?? basename($file, '.json'),
                    'source'         => $source,
                    'label'          => $label,
                    'topic'          => $decoded['topic'] ?? '',
                    'score'          => $score,
                    'classification' => $this->scoreAggregator->classify($score),
                    'colour'         => $this->scoreAggregator->classificationColour($score),
                    'result'         => $result,
                    'topDetectors'   => $topDetectors,
                    'wordCount'      => str_word_count($text),
                ];

                $rows[] = $row;

                // Track candidates for sample deep dives
                if ($label === 'human' && $result === 'pass') {
                    if ($bestHuman === null || $score < $bestHuman['score']) {
                        $bestHuman = [...$row, 'text' => $text, 'report' => $report];
                    }
                }
                if ($label === 'ai') {
                    if ($highestAi === null || $score > $highestAi['score']) {
                        $highestAi = [...$row, 'text' => $text, 'report' => $report];
                    }
                    if ($result === 'uncertain' && ($uncertainAi === null || $score > $uncertainAi['score'])) {
                        $uncertainAi = [...$row, 'text' => $text, 'report' => $report];
                    }
                    if ($lowestAi === null || $score < $lowestAi['score']) {
                        $lowestAi = [...$row, 'text' => $text, 'report' => $report];
                    }
                }
            }
        }

        // Build curated samples with full analysis
        foreach ([$bestHuman, $highestAi, $uncertainAi, $lowestAi] as $candidate) {
            if ($candidate === null) {
                continue;
            }
            /** @var \App\Services\AiDetector\DTOs\AnalysisReport $rpt */
            $rpt = $candidate['report'];
            $samples[] = [
                'id'               => $candidate['id'],
                'source'           => $candidate['source'],
                'label'            => $candidate['label'],
                'topic'            => $candidate['topic'],
                'score'            => $candidate['score'],
                'classification'   => $candidate['classification'],
                'colour'           => $candidate['colour'],
                'result'           => $candidate['result'],
                'wordCount'        => $candidate['wordCount'],
                'text'             => mb_substr($candidate['text'], 0, 800) . (mb_strlen($candidate['text']) > 800 ? '…' : ''),
                'detectorResults'  => array_map(fn ($d) => [
                    'category'    => $d->category,
                    'score'       => $d->score,
                    'cap'         => $d->cap,
                    'occurrences' => $d->occurrences,
                    'explanation' => $d->explanation,
                    'confidence'  => $d->confidence,
                ], $rpt->detectorResults),
                'linguisticFactors' => array_map(fn ($f) => [
                    'label'        => $f->label,
                    'rawValue'     => $f->rawValue,
                    'displayValue' => $f->displayValue,
                    'status'       => $f->status,
                    'explanation'  => $f->explanation,
                ], $rpt->linguisticFactors),
            ];
        }

        // Sort rows by source order then score descending
        $sourceOrder = array_flip(self::SOURCES);
        usort($rows, static function (array $a, array $b) use ($sourceOrder): int {
            $srcCmp = ($sourceOrder[$a['source']] ?? 99) <=> ($sourceOrder[$b['source']] ?? 99);
            return $srcCmp !== 0 ? $srcCmp : $b['score'] <=> $a['score'];
        });

        // Summary by source
        $summary = [];
        foreach (self::SOURCES as $src) {
            $srcRows = array_filter($rows, fn ($r) => $r['source'] === $src);
            $total   = count($srcRows);
            $pass    = count(array_filter($srcRows, fn ($r) => $r['result'] === 'pass'));
            $fail    = count(array_filter($srcRows, fn ($r) => $r['result'] === 'fail'));
            $uncertain = count(array_filter($srcRows, fn ($r) => $r['result'] === 'uncertain'));

            $summary[$src] = [
                'total'     => $total,
                'pass'      => $pass,
                'fail'      => $fail,
                'uncertain' => $uncertain,
                'accuracy'  => $total > 0 ? round($pass / $total * 100, 1) : 0,
            ];
        }

        // Overall stats
        $aiRows    = array_filter($rows, fn ($r) => $r['label'] === 'ai');
        $humanRows = array_filter($rows, fn ($r) => $r['label'] === 'human');

        $aiTotal    = count($aiRows);
        $humanTotal = count($humanRows);
        $total      = count($rows);

        $aiCorrect    = count(array_filter($aiRows,    fn ($r) => $r['result'] === 'pass'));
        $humanCorrect = count(array_filter($humanRows, fn ($r) => $r['result'] === 'pass'));
        $overallPass  = count(array_filter($rows,      fn ($r) => $r['result'] === 'pass'));
        $uncertain    = count(array_filter($rows,      fn ($r) => $r['result'] === 'uncertain'));

        $fp = count(array_filter($humanRows, fn ($r) => $r['score'] >= self::AI_THRESHOLD));
        $fn = count(array_filter($aiRows,    fn ($r) => $r['score'] < self::HUMAN_THRESHOLD));

        $tp = $aiCorrect;
        $tn = $humanCorrect;

        return [
            'rows'    => array_values($rows),
            'samples' => $samples,
            'summary' => $summary,
            'overall' => [
                'total'           => $total,
                'pass'            => $overallPass,
                'fail'            => $total - $overallPass - $uncertain,
                'uncertain'       => $uncertain,
                'overallAccuracy' => $total > 0 ? round($overallPass / $total * 100, 1) : 0,
                'aiDetectionRate' => $aiTotal > 0 ? round($aiCorrect / $aiTotal * 100, 1) : 0,
                'humanPassRate'   => $humanTotal > 0 ? round($humanCorrect / $humanTotal * 100, 1) : 0,
                'falsePositives'  => $fp,
                'falseNegatives'  => $fn,
                'truePositives'   => $tp,
                'trueNegatives'   => $tn,
            ],
            'thresholds' => [
                'ai'    => self::AI_THRESHOLD,
                'human' => self::HUMAN_THRESHOLD,
            ],
            'generatedAt' => now()->toIso8601String(),
        ];
    }

    private function classifyResult(int $score, string $label): string
    {
        if ($label === 'ai') {
            return $score >= self::AI_THRESHOLD ? 'pass'
                : ($score < self::HUMAN_THRESHOLD ? 'fail' : 'uncertain');
        }

        return $score < self::HUMAN_THRESHOLD ? 'pass'
            : ($score >= self::AI_THRESHOLD ? 'fail' : 'uncertain');
    }
}
