<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\AiDetector\AnalysisService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Runs all JSON fixtures through the AnalysisService and prints an accuracy report.
 *
 * Usage:
 *   php artisan accuracy:report
 *   php artisan accuracy:report --source=ai-claude
 *   php artisan accuracy:report --verbose
 */
class AccuracyReport extends Command
{
    protected $signature = 'accuracy:report
                            {--source= : Filter to a single source directory (human, ai-claude, ai-gemini, ai-chatgpt)}
                            {--details : Show the top-scoring detectors for each sample}';

    protected $description = 'Run all COP21 accuracy fixtures through the detector and print a report';

    /**
     * Score thresholds (mirrors ScoreAggregator::classify logic).
     */
    private const int AI_THRESHOLD    = 60;   // >= 60 → predicted AI
    private const int HUMAN_THRESHOLD = 30;   // <  30 → predicted Human
    // 30–59 → uncertain

    public function handle(AnalysisService $service): int
    {
        $fixturesBase = base_path('tests/fixtures');
        $filterSource = $this->option('source');
        $verbose      = (bool) $this->option('details');

        // ------------------------------------------------------------------ //
        // Discover source directories                                          //
        // ------------------------------------------------------------------ //
        $allSources = ['human', 'ai-claude', 'ai-gemini', 'ai-chatgpt'];

        if ($filterSource !== null) {
            if (! in_array($filterSource, $allSources, true)) {
                $this->error("Unknown source \"{$filterSource}\". Valid sources: " . implode(', ', $allSources));
                return self::FAILURE;
            }
            $allSources = [$filterSource];
        }

        // ------------------------------------------------------------------ //
        // Load fixtures                                                        //
        // ------------------------------------------------------------------ //
        /** @var array<string, array<array{fixture: array<string,mixed>, file: string}>> $bySource */
        $bySource = [];

        foreach ($allSources as $source) {
            $dir = $fixturesBase . DIRECTORY_SEPARATOR . $source;

            if (! is_dir($dir)) {
                $bySource[$source] = [];
                continue;
            }

            $jsonFiles = glob($dir . DIRECTORY_SEPARATOR . '*.json') ?: [];

            $bySource[$source] = [];
            foreach ($jsonFiles as $file) {
                $decoded = json_decode(file_get_contents($file), true);
                if (! is_array($decoded)) {
                    $this->warn("Skipping unreadable fixture: {$file}");
                    continue;
                }
                $bySource[$source][] = ['fixture' => $decoded, 'file' => $file];
            }
        }

        // ------------------------------------------------------------------ //
        // Run analysis                                                         //
        // ------------------------------------------------------------------ //
        /**
         * Each row:
         *   angle, source, score, label, result ('pass'|'fail'|'uncertain'), detectorResults
         *
         * @var array<array{angle:int|string,source:string,score:int,label:string,result:string,detectorResults:array}> $rows
         */
        $rows = [];

        foreach ($bySource as $source => $entries) {
            foreach ($entries as $entry) {
                $fixture = $entry['fixture'];
                $text    = $fixture['text'] ?? '';
                $label   = $fixture['label'] ?? 'human';
                $angle   = $fixture['angle'] ?? '?';

                $report = $service->analyse($text);
                $score  = $report->totalScore;

                $result = $this->classifyResult($score, $label);

                $rows[] = [
                    'angle'           => $angle,
                    'source'          => $source,
                    'score'           => $score,
                    'label'           => $label,
                    'result'          => $result,
                    'detectorResults' => $report->detectorResults,
                ];
            }
        }

        // Sort rows by source order then angle
        $sourceOrder = array_flip($allSources);
        usort($rows, static function (array $a, array $b) use ($sourceOrder): int {
            $srcCmp = ($sourceOrder[$a['source']] ?? 99) <=> ($sourceOrder[$b['source']] ?? 99);
            if ($srcCmp !== 0) {
                return $srcCmp;
            }
            return (int) $a['angle'] <=> (int) $b['angle'];
        });

        // ------------------------------------------------------------------ //
        // Output                                                               //
        // ------------------------------------------------------------------ //
        $this->line('');
        $this->line('<fg=white;options=bold>ACCURACY REPORT — Paris Climate Agreement (COP21)</>');
        $this->line(str_repeat('=', 50));

        // ---- Per-sample results ------------------------------------------ //
        $this->line('');
        $this->line('<options=bold>PER-SAMPLE RESULTS</>');

        $colWidths = ['angle' => 6, 'source' => 12, 'score' => 7, 'expected' => 10, 'result' => 12];
        $header = sprintf(
            '%-' . $colWidths['angle']    . 's  ' .
            '%-' . $colWidths['source']   . 's  ' .
            '%-' . $colWidths['score']    . 's  ' .
            '%-' . $colWidths['expected'] . 's  ' .
            '%s',
            'Angle', 'Source', 'Score', 'Expected', 'Result'
        );
        $this->line($header);
        $this->line(str_repeat('─', 50));

        foreach ($rows as $row) {
            $resultTag  = $this->formatResult($row['result']);
            $sourceLabel = $row['source'];

            $line = sprintf(
                '%-' . $colWidths['angle']    . 's  ' .
                '%-' . $colWidths['source']   . 's  ' .
                '%-' . $colWidths['score']    . 's  ' .
                '%-' . $colWidths['expected'] . 's  ' .
                '%s',
                $row['angle'],
                $sourceLabel,
                $row['score'],
                $row['label'],
                $resultTag
            );
            $this->line($line);

            if ($verbose) {
                // Show top 3 scoring detectors
                $detectors = $row['detectorResults'];
                usort($detectors, static fn($a, $b): int => $b->score <=> $a->score);
                $top = array_slice($detectors, 0, 3);
                foreach ($top as $d) {
                    if ($d->score > 0) {
                        $this->line(sprintf(
                            '         <fg=gray>  ↳ %-28s score: %d  matches: %d</>',
                            $d->category,
                            $d->score,
                            $d->occurrences
                        ));
                    }
                }
            }
        }

        // ---- Summary by source ------------------------------------------- //
        $this->line('');
        $this->line('<options=bold>SUMMARY BY SOURCE</>');

        $summaryHeader = sprintf(
            '%-14s  %5s  %4s  %4s  %9s  %s',
            'Source', 'Total', 'Pass', 'Fail', 'Uncertain', 'Accuracy'
        );
        $this->line($summaryHeader);
        $this->line(str_repeat('─', 54));

        /** @var array<string,array{total:int,pass:int,fail:int,uncertain:int}> $summary */
        $summary = [];
        foreach ($allSources as $src) {
            $summary[$src] = ['total' => 0, 'pass' => 0, 'fail' => 0, 'uncertain' => 0];
        }
        foreach ($rows as $row) {
            $s = $row['source'];
            $summary[$s]['total']++;
            $summary[$s][$row['result']]++;
        }

        foreach ($allSources as $src) {
            $s = $summary[$src];

            if ($s['total'] === 0) {
                // Check whether the directory is truly absent or just empty
                $dir = $fixturesBase . DIRECTORY_SEPARATOR . $src;
                if (! is_dir($dir) || empty(glob($dir . DIRECTORY_SEPARATOR . '*.json'))) {
                    $this->line(sprintf('%-14s  <fg=gray>(no fixtures yet)</>', $src));
                    continue;
                }
            }

            $accuracy = $s['total'] > 0
                ? number_format(($s['pass'] / $s['total']) * 100, 1) . '%'
                : '—';

            $passStr      = $s['total'] > 0 ? (string) $s['pass']      : '—';
            $failStr      = $s['total'] > 0 ? (string) $s['fail']      : '—';
            $uncertainStr = $s['total'] > 0 ? (string) $s['uncertain'] : '—';
            $totalStr     = $s['total'] > 0 ? (string) $s['total']     : '—';

            $this->line(sprintf(
                '%-14s  %5s  %4s  %4s  %9s  %s',
                $src, $totalStr, $passStr, $failStr, $uncertainStr, $accuracy
            ));
        }

        // ---- Overall stats ----------------------------------------------- //
        $this->line('');
        $this->line('<options=bold>OVERALL</>');

        $aiRows    = array_filter($rows, static fn(array $r): bool => $r['label'] === 'ai');
        $humanRows = array_filter($rows, static fn(array $r): bool => $r['label'] === 'human');
        $allRows   = $rows;

        $aiTotal    = count($aiRows);
        $humanTotal = count($humanRows);
        $total      = count($allRows);

        $aiCorrect    = count(array_filter($aiRows,    static fn(array $r): bool => $r['result'] === 'pass'));
        $humanCorrect = count(array_filter($humanRows, static fn(array $r): bool => $r['result'] === 'pass'));
        $overallPass  = count(array_filter($allRows,   static fn(array $r): bool => $r['result'] === 'pass'));
        $uncertain    = count(array_filter($allRows,   static fn(array $r): bool => $r['result'] === 'uncertain'));

        $this->line(sprintf(
            'AI detection rate (>= %d):    %s',
            self::AI_THRESHOLD,
            $this->fraction($aiCorrect, $aiTotal)
        ));
        $this->line(sprintf(
            'Human pass rate (< %d):       %s',
            self::HUMAN_THRESHOLD,
            $this->fraction($humanCorrect, $humanTotal)
        ));
        $this->line(sprintf(
            'Overall accuracy:             %s',
            $this->fraction($overallPass, $total)
        ));
        $this->line(sprintf(
            'Uncertain (%d–%d):            %s',
            self::HUMAN_THRESHOLD,
            self::AI_THRESHOLD - 1,
            $this->fraction($uncertain, $total)
        ));

        // ---- False positives / negatives ---------------------------------- //
        $fp = count(array_filter($humanRows, static fn(array $r): bool => $r['score'] >= self::AI_THRESHOLD));
        $fn = count(array_filter($aiRows,    static fn(array $r): bool => $r['score'] < self::HUMAN_THRESHOLD));

        $this->line('');
        $this->line(sprintf('FALSE POSITIVES (human text scored >= %d):  %d', self::AI_THRESHOLD, $fp));
        $this->line(sprintf('FALSE NEGATIVES (AI text scored < %d):      %d', self::HUMAN_THRESHOLD, $fn));
        $this->line('');

        // ---- Empty source notices ---------------------------------------- //
        foreach ($allSources as $src) {
            $dir = $fixturesBase . DIRECTORY_SEPARATOR . $src;
            $jsonFiles = is_dir($dir) ? (glob($dir . DIRECTORY_SEPARATOR . '*.json') ?: []) : [];
            if (empty($jsonFiles)) {
                $this->line(
                    "<fg=yellow>No " . ucfirst(str_replace('ai-', '', $src)) .
                    " fixtures yet — add JSON files to tests/fixtures/{$src}/</>"
                );
            }
        }

        return self::SUCCESS;
    }

    // ----------------------------------------------------------------------- //
    // Helpers                                                                  //
    // ----------------------------------------------------------------------- //

    /**
     * Determine whether the prediction is correct, false, or uncertain.
     *
     * @param 'human'|'ai' $label
     * @return 'pass'|'fail'|'uncertain'
     */
    private function classifyResult(int $score, string $label): string
    {
        if ($label === 'ai') {
            if ($score >= self::AI_THRESHOLD) {
                return 'pass';
            }
            if ($score < self::HUMAN_THRESHOLD) {
                return 'fail';
            }
            return 'uncertain';
        }

        // label === 'human'
        if ($score < self::HUMAN_THRESHOLD) {
            return 'pass';
        }
        if ($score >= self::AI_THRESHOLD) {
            return 'fail';
        }
        return 'uncertain';
    }

    private function formatResult(string $result): string
    {
        return match ($result) {
            'pass'      => '<fg=green>✓ PASS</>',
            'fail'      => '<fg=red>✗ FAIL</>',
            'uncertain' => '<fg=yellow>~ UNCERTAIN</>',
            default     => $result,
        };
    }

    /**
     * Format "N/D (PP.P%)" or "0/0 (—)" when denominator is zero.
     */
    private function fraction(int $numerator, int $denominator): string
    {
        if ($denominator === 0) {
            return '—/— (—)';
        }
        $pct = number_format(($numerator / $denominator) * 100, 1);
        return "{$numerator}/{$denominator} ({$pct}%)";
    }
}
