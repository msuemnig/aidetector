import React, { useState } from 'react';
import { AnalysisReport } from '../types';

interface CopyButtonProps {
    report: AnalysisReport;
}

function buildCopyText(report: AnalysisReport): string {
    const lines: string[] = [];

    lines.push('AI Text Detector — Analysis Report');
    const analysedAt = new Date(report.analysedAt).toLocaleString(undefined, {
        day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit',
    });
    lines.push(`Analysed: ${analysedAt}`);
    lines.push('');
    lines.push(`RESULT: ${report.classification} (${report.totalScore}/100)`);
    lines.push('');
    const confOrder: Record<string, number> = { HIGH: 0, MEDIUM: 1, LOW: 2 };

    lines.push('--- LINGUISTIC FACTORS ---');

    for (const factor of [...report.linguisticFactors].sort((a, b) => (confOrder[a.confidence] ?? 1) - (confOrder[b.confidence] ?? 1))) {
        lines.push(`[${factor.confidence}] ${factor.label}: ${factor.displayValue} (${factor.status})`);
    }

    lines.push('');
    lines.push('--- PATTERN DETECTIONS ---');

    for (const result of [...report.detectorResults].sort((a, b) => (confOrder[a.confidence] ?? 1) - (confOrder[b.confidence] ?? 1))) {
        lines.push(`[${result.confidence}] ${result.category}: ${result.occurrences} occurrences, ${result.score}/${result.cap} pts`);
        lines.push(`  ${result.explanation}`);
    }

    return lines.join('\n');
}

export default function CopyButton({ report }: CopyButtonProps) {
    const [copied, setCopied] = useState(false);

    const handleCopy = async () => {
        try {
            await navigator.clipboard.writeText(buildCopyText(report));
            setCopied(true);
            setTimeout(() => setCopied(false), 2000);
        } catch {
            // Clipboard API not available — silently fail
        }
    };

    return (
        <button
            onClick={handleCopy}
            className="inline-flex items-center gap-2 px-4 py-2 bg-gray-800 dark:bg-gray-700 hover:bg-gray-700 dark:hover:bg-gray-600 text-white text-sm font-medium rounded-lg transition-colors"
        >
            {copied ? (
                <>
                    {/* Tick icon */}
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        className="w-4 h-4"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        strokeWidth={2.5}
                    >
                        <polyline points="20 6 9 17 4 12" />
                    </svg>
                    Copied!
                </>
            ) : (
                <>
                    {/* Clipboard icon */}
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        className="w-4 h-4"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        strokeWidth={2}
                    >
                        <rect x="9" y="2" width="6" height="4" rx="1" ry="1" />
                        <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2" />
                    </svg>
                    Copy Results
                </>
            )}
        </button>
    );
}
