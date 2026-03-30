<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\AiDetector\AccuracyReportService;
use Illuminate\Console\Command;

/**
 * Pre-computes the accuracy report and writes it to a static JSON file.
 *
 * Run as part of the build/deploy pipeline:
 *   php artisan accuracy:export
 *
 * The Testing page controller reads this file instead of computing live.
 */
class AccuracyExport extends Command
{
    protected $signature = 'accuracy:export';

    protected $description = 'Pre-compute accuracy report to storage/accuracy-report.json';

    public function handle(AccuracyReportService $service): int
    {
        $this->info('Running 136 fixtures through analysis pipeline...');

        $data = $service->generate();

        $path = storage_path('accuracy-report.json');
        file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $this->info("Written to {$path}");
        $this->info(sprintf(
            'Results: %s overall accuracy, %s AI detection, %s human pass.',
            $data['overall']['overallAccuracy'] . '%',
            $data['overall']['aiDetectionRate'] . '%',
            $data['overall']['humanPassRate'] . '%',
        ));

        return self::SUCCESS;
    }
}
