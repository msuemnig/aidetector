import React, { useEffect, useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import ScoreBadge from '../../Components/ScoreBadge';
import HighlightedText from '../../Components/HighlightedText';
import LinguisticFactorRow from '../../Components/LinguisticFactorRow';
import DetectorResultRow from '../../Components/DetectorResultRow';
import CopyButton from '../../Components/CopyButton';
import { AnalysisReport } from '../../types';

function formatAnalysedAt(iso: string): string {
    const d = new Date(iso);
    return d.toLocaleString(undefined, {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

interface ResultsProps {
    report?: AnalysisReport;
    error?: string;
}

function StatCard({ label, value }: { label: string; value: string }) {
    return (
        <div className="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg p-4 text-center">
            <div className="text-lg font-bold text-gray-900 dark:text-white mb-0.5">
                {value}
            </div>
            <div className="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                {label}
            </div>
        </div>
    );
}

export default function Results({ report, error }: ResultsProps) {
    const [textOpen, setTextOpen] = useState(true);

    // Warn before leaving
    useEffect(() => {
        const handler = (e: BeforeUnloadEvent) => {
            e.preventDefault();
            e.returnValue = 'Your results will be lost if you leave this page.';
        };
        window.addEventListener('beforeunload', handler);
        return () => window.removeEventListener('beforeunload', handler);
    }, []);

    // Redirect if no report and no error
    useEffect(() => {
        if (!report && !error) {
            router.visit('/');
        }
    }, [report, error]);

    // Error state
    if (error) {
        return (
            <AppLayout>
                <Head title="Results" />
                <div className="max-w-3xl mx-auto">
                    <div className="bg-red-50 dark:bg-red-950 border border-red-200 dark:border-red-800 rounded-lg p-6 text-center">
                        <div className="text-red-600 dark:text-red-400 mb-2">
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                className="w-10 h-10 mx-auto mb-3"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                strokeWidth={1.5}
                            >
                                <circle cx="12" cy="12" r="10" />
                                <line x1="12" y1="8" x2="12" y2="12" />
                                <line x1="12" y1="16" x2="12.01" y2="16" />
                            </svg>
                        </div>
                        <h2 className="text-lg font-semibold text-red-800 dark:text-red-200 mb-2">
                            Analysis Failed
                        </h2>
                        <p className="text-sm text-red-700 dark:text-red-300 mb-5">
                            {error}
                        </p>
                        <button
                            onClick={() => router.visit('/')}
                            className="px-5 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors"
                        >
                            Try Again
                        </button>
                    </div>
                </div>
            </AppLayout>
        );
    }

    if (!report) {
        return null;
    }

    // Sort detector results: by confidence (HIGH > MEDIUM > LOW), then occurrences > 0 first
    const confidenceOrder: Record<string, number> = { HIGH: 0, MEDIUM: 1, LOW: 2 };
    const sortedResults = [...report.detectorResults].sort((a, b) => {
        const confA = confidenceOrder[a.confidence] ?? 1;
        const confB = confidenceOrder[b.confidence] ?? 1;
        if (confA !== confB) return confA - confB;
        // Within same confidence, show hits first
        return (b.occurrences > 0 ? 1 : 0) - (a.occurrences > 0 ? 1 : 0);
    });

    // Sort linguistic factors: by confidence (HIGH > MEDIUM > LOW)
    const sortedFactors = [...report.linguisticFactors].sort((a, b) => {
        return (confidenceOrder[a.confidence] ?? 1) - (confidenceOrder[b.confidence] ?? 1);
    });

    return (
        <AppLayout>
            <Head title="Results" />

            <div className="max-w-3xl mx-auto space-y-6">

                {/* Page heading */}
                <div>
                    <h2 className="text-2xl font-bold text-gray-900 dark:text-white mb-1">
                        Analysis Results
                    </h2>
                    <p className="text-sm text-gray-500 dark:text-gray-400">
                        Analysed at {formatAnalysedAt(report.analysedAt)}
                    </p>
                </div>

                {/* 1. Score badge */}
                <ScoreBadge
                    score={report.totalScore}
                    classification={report.classification}
                    colour={report.classificationColour}
                />

                {/* 2. Text stats */}
                <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <StatCard
                        label="Words"
                        value={report.wordCount.toLocaleString()}
                    />
                    <StatCard
                        label="Characters"
                        value={report.characterCount.toLocaleString()}
                    />
                    <StatCard
                        label="Avg word length"
                        value={`${report.averageWordLength.toFixed(1)} chars`}
                    />
                    <StatCard
                        label="Analysed at"
                        value={formatAnalysedAt(report.analysedAt)}
                    />
                </div>

                {/* 3. Highlighted text (collapsible) */}
                <div className="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                    <button
                        onClick={() => setTextOpen((prev) => !prev)}
                        className="w-full flex items-center justify-between px-4 py-3 text-left hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
                        aria-expanded={textOpen}
                    >
                        <span className="font-semibold text-gray-900 dark:text-white text-sm">
                            Analysed Text
                        </span>
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            className={`w-4 h-4 text-gray-500 transition-transform duration-200 ${textOpen ? 'rotate-180' : ''}`}
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            strokeWidth={2}
                        >
                            <polyline points="6 9 12 15 18 9" />
                        </svg>
                    </button>

                    {textOpen && (
                        <div className="px-4 pb-4">
                            <HighlightedText
                                text={report.text}
                                highlights={report.highlights}
                            />
                        </div>
                    )}
                </div>

                {/* 4. Linguistic Factors */}
                <section>
                    <h3 className="text-base font-semibold text-gray-900 dark:text-white mb-3">
                        Linguistic Factors
                    </h3>
                    <div className="space-y-2">
                        {sortedFactors.map((factor, index) => (
                            <LinguisticFactorRow key={index} factor={factor} />
                        ))}
                    </div>
                </section>

                {/* 5. Pattern Detections */}
                <section>
                    <h3 className="text-base font-semibold text-gray-900 dark:text-white mb-3">
                        Pattern Detections
                    </h3>
                    {sortedResults.length > 0 ? (
                        <div className="space-y-2">
                            {sortedResults.map((result, index) => (
                                <DetectorResultRow key={index} result={result} />
                            ))}
                        </div>
                    ) : (
                        <p className="text-sm text-gray-500 dark:text-gray-400">
                            No pattern detections to display.
                        </p>
                    )}
                </section>

                {/* 6. Action buttons */}
                <div className="flex flex-wrap items-center gap-3 pt-2 pb-6 border-t border-gray-200 dark:border-gray-700">
                    <CopyButton report={report} />
                    <button
                        onClick={() => router.visit('/')}
                        className="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 text-sm font-medium rounded-lg transition-colors"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            className="w-4 h-4"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            strokeWidth={2}
                        >
                            <line x1="19" y1="12" x2="5" y2="12" />
                            <polyline points="12 19 5 12 12 5" />
                        </svg>
                        Analyse Another Text
                    </button>
                </div>

            </div>
        </AppLayout>
    );
}
