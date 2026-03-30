<?php

declare(strict_types=1);

namespace App\Services\AiDetector;

use App\Services\AiDetector\DTOs\AnalysisReport;
use App\Services\AiDetector\DTOs\DetectorResult;
use App\Services\AiDetector\DTOs\LinguisticFactor;
use App\Services\AiDetector\Detectors\AdverbialOpenerDetector;
use App\Services\AiDetector\Detectors\AiVocabularyDetector;
use App\Services\AiDetector\Detectors\ContractionDetector;
use App\Services\AiDetector\Detectors\ElegantVariationDetector;
use App\Services\AiDetector\Detectors\EpistemicMarkerDetector;
use App\Services\AiDetector\Detectors\FalseRangeDetector;
use App\Services\AiDetector\Detectors\FormulaicImportanceDetector;
use App\Services\AiDetector\Detectors\GrandioseNounDetector;
use App\Services\AiDetector\Detectors\NegativeParallelismDetector;
use App\Services\AiDetector\Detectors\OutlineConclusionDetector;
use App\Services\AiDetector\Detectors\OvergeneralisationDetector;
use App\Services\AiDetector\Detectors\PromotionalLanguageDetector;
use App\Services\AiDetector\Detectors\RuleOfThreeDetector;
use App\Services\AiDetector\Detectors\SuperficialAnalysisDetector;
use App\Services\AiDetector\Detectors\TrailingParticipleDetector;
use App\Services\AiDetector\Detectors\UndueEmphasisDetector;
use App\Services\AiDetector\Detectors\VagueAttributionDetector;
use App\Services\AiDetector\Linguistic\FleschKincaidAnalyser;
use App\Services\AiDetector\Linguistic\FunctionWordAnalyser;
use App\Services\AiDetector\Linguistic\HapaxLegomenonAnalyser;
use App\Services\AiDetector\Linguistic\LexicalDiversityAnalyser;
use App\Services\AiDetector\Linguistic\ParagraphVarianceAnalyser;
use App\Services\AiDetector\Linguistic\PassiveVoiceAnalyser;
use App\Services\AiDetector\Linguistic\PunctuationAnalyser;
use App\Services\AiDetector\Linguistic\NgramRepetitionAnalyser;
use App\Services\AiDetector\Linguistic\RareWordAnalyser;
use App\Services\AiDetector\Linguistic\SentenceLengthAnalyser;
use App\Services\AiDetector\Linguistic\SentenceOpenerDiversityAnalyser;
use App\Services\AiDetector\Linguistic\ShannonEntropyAnalyser;
use App\Services\AiDetector\Linguistic\TransitionWordAnalyser;
use App\Services\AiDetector\Linguistic\ZipfAnalyser;

/**
 * Orchestrates the full analysis pipeline and returns an AnalysisReport.
 */
final class AnalysisService
{
    /**
     * One highlight colour per detector category (Tailwind class names).
     */
    private const array CATEGORY_COLOURS = [
        'AI Vocabulary'        => 'bg-yellow-200 dark:bg-yellow-800',
        'Rule of Three'        => 'bg-blue-200 dark:bg-blue-800',
        'Negative Parallelism' => 'bg-green-200 dark:bg-green-800',
        'Outline & Conclusion' => 'bg-red-200 dark:bg-red-800',
        'False Range'          => 'bg-orange-200 dark:bg-orange-800',
        'Vague Attribution'    => 'bg-purple-200 dark:bg-purple-800',
        'Superficial Analysis' => 'bg-pink-200 dark:bg-pink-800',
        'Overgeneralisation'   => 'bg-amber-200 dark:bg-amber-800',
        'Undue Emphasis'       => 'bg-rose-200 dark:bg-rose-800',
        'Promotional Language' => 'bg-cyan-200 dark:bg-cyan-800',
        'Elegant Variation'    => 'bg-lime-200 dark:bg-lime-800',
    ];

    /** @var DetectorInterface[] */
    private array $detectors;

    /** @var array<object> */
    private array $linguisticAnalysers;

    private ScoreAggregator $aggregator;

    public function __construct()
    {
        $this->detectors = [
            // AI signal detectors (positive score)
            new AiVocabularyDetector(),
            new RuleOfThreeDetector(),
            new NegativeParallelismDetector(),
            new OutlineConclusionDetector(),
            new FalseRangeDetector(),
            new VagueAttributionDetector(),
            new SuperficialAnalysisDetector(),
            new OvergeneralisationDetector(),
            new UndueEmphasisDetector(),
            new PromotionalLanguageDetector(),
            new ElegantVariationDetector(),
            // New research-backed pattern detectors (March 2026)
            new TrailingParticipleDetector(),
            new FormulaicImportanceDetector(),
            new AdverbialOpenerDetector(),
            new GrandioseNounDetector(),
            // Human signal detectors (negative score)
            new EpistemicMarkerDetector(),
            new ContractionDetector(),
        ];

        $this->linguisticAnalysers = [
            // Existing AI-signal analysers
            new LexicalDiversityAnalyser(),
            new SentenceLengthAnalyser(),
            new PassiveVoiceAnalyser(),
            new TransitionWordAnalyser(),
            new FleschKincaidAnalyser(),
            new PunctuationAnalyser(),
            new RareWordAnalyser(),
            new ZipfAnalyser(),
            // New human-signal analysers
            new HapaxLegomenonAnalyser(),
            new ParagraphVarianceAnalyser(),
            new FunctionWordAnalyser(),
            // New research-backed analysers (March 2026)
            new ShannonEntropyAnalyser(),
            new NgramRepetitionAnalyser(),
            new SentenceOpenerDiversityAnalyser(),
        ];

        $this->aggregator = new ScoreAggregator();
    }

    public function analyse(string $text): AnalysisReport
    {
        // --- Detector pass ---
        $detectorResults = [];
        foreach ($this->detectors as $detector) {
            $detectorResults[] = $detector->analyse($text);
        }

        // --- Linguistic pass ---
        $linguisticFactors = [];
        foreach ($this->linguisticAnalysers as $analyser) {
            $linguisticFactors[] = $analyser->analyse($text);
        }

        // --- Aggregate score ---
        $totalScore            = $this->aggregator->aggregate($detectorResults, $linguisticFactors);
        $classification        = $this->aggregator->classify($totalScore);
        $classificationColour  = $this->aggregator->classificationColour($totalScore);

        // --- Text statistics ---
        $words            = preg_split('/\s+/u', trim($text), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $wordCount        = count($words);
        $characterCount   = mb_strlen($text);
        $totalWordChars   = array_sum(array_map('mb_strlen', $words));
        $averageWordLength = $wordCount > 0 ? round($totalWordChars / $wordCount, 1) : 0.0;

        // --- Build highlight map ---
        $highlights = $this->buildHighlights($text, $detectorResults);

        return new AnalysisReport(
            text: $text,
            wordCount: $wordCount,
            characterCount: $characterCount,
            averageWordLength: $averageWordLength,
            analysedAt: now()->toIso8601String(),
            totalScore: $totalScore,
            classification: $classification,
            classificationColour: $classificationColour,
            detectorResults: $detectorResults,
            linguisticFactors: $linguisticFactors,
            highlights: $highlights,
        );
    }

    /**
     * Build the server-side highlight map.
     * Finds character offsets for each match and assigns category colours.
     * Overlapping matches keep the first one applied.
     *
     * @param  DetectorResult[] $detectorResults
     * @return array<array{start:int,end:int,category:string,colour:string}>
     */
    private function buildHighlights(string $text, array $detectorResults): array
    {
        $raw = [];

        foreach ($detectorResults as $result) {
            $colour = self::CATEGORY_COLOURS[$result->category] ?? 'bg-gray-200 dark:bg-gray-700';

            foreach ($result->matches as $match) {
                $offset = 0;
                $lower  = mb_strtolower($text);
                $needle = mb_strtolower((string) $match);

                while (($pos = mb_strpos($lower, $needle, $offset)) !== false) {
                    $raw[] = [
                        'start'    => $pos,
                        'end'      => $pos + mb_strlen($needle),
                        'category' => $result->category,
                        'colour'   => $colour,
                    ];
                    $offset = $pos + 1;
                }
            }
        }

        // Sort by start position
        usort($raw, static fn(array $a, array $b): int => $a['start'] <=> $b['start']);

        // Remove overlaps — keep the first match applied
        $highlights  = [];
        $coveredUpTo = -1;

        foreach ($raw as $h) {
            if ($h['start'] >= $coveredUpTo) {
                $highlights[]  = $h;
                $coveredUpTo   = $h['end'];
            }
        }

        return $highlights;
    }
}
