<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\AiDetector\AccuracyReportService;
use App\Services\AiDetector\AnalysisService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

final class AnalyserController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Analyser/Index');
    }

    public function about(): Response
    {
        return Inertia::render('About');
    }

    public function testing(AccuracyReportService $reportService): Response
    {
        $data = Cache::remember('accuracy-report-v1', 3600, fn () => $reportService->generate());

        return Inertia::render('Testing', [
            'report' => $data,
        ]);
    }

    public function analyse(Request $request): Response
    {
        $validated = $request->validate([
            'text' => [
                'required',
                'string',
                'max:50000',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (str_word_count($value) < 300) {
                        $fail('Please paste at least 300 words. Shorter texts do not contain enough linguistic signal for reliable detection.');
                    }
                },
            ],
        ]);

        try {
            $service = new AnalysisService();
            $report  = $service->analyse($validated['text']);

            return Inertia::render('Analyser/Results', [
                'report' => $report,
            ]);
        } catch (Throwable $e) {
            report($e);

            return Inertia::render('Analyser/Results', [
                'error' => 'An unexpected error occurred during analysis. Please try again.',
            ]);
        }
    }
}
