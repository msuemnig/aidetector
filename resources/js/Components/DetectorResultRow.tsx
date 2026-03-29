import React from 'react';
import { DetectorResult } from '../types';

interface DetectorResultRowProps {
    result: DetectorResult;
}

export default function DetectorResultRow({ result }: DetectorResultRowProps) {
    const progressPercent = result.cap > 0
        ? Math.min(Math.round((result.score / result.cap) * 100), 100)
        : 0;

    const progressColour =
        progressPercent >= 75
            ? 'bg-red-500'
            : progressPercent >= 40
            ? 'bg-yellow-500'
            : 'bg-blue-500';

    return (
        <div className="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
            {/* Header row */}
            <div className="flex items-center justify-between gap-3 mb-3">
                <div className="flex items-center gap-2">
                    <span className="font-semibold text-gray-900 dark:text-white text-sm">
                        {result.category}
                    </span>
                    <span className={`text-[10px] font-semibold px-1.5 py-0.5 rounded ${
                        result.confidence === 'HIGH'   ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' :
                        result.confidence === 'MEDIUM' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400' :
                        'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400'
                    }`}>
                        {result.confidence}
                    </span>
                </div>
                <span className="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 text-xs font-medium px-2 py-0.5 rounded-full shrink-0">
                    {result.occurrences} {result.occurrences === 1 ? 'occurrence' : 'occurrences'}
                </span>
            </div>

            {/* Score / cap + progress bar */}
            <div className="mb-2">
                <div className="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 mb-1">
                    <span>{result.score}/{result.cap} pts</span>
                    <span>{progressPercent}%</span>
                </div>
                <div className="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                    <div
                        className={`${progressColour} h-2 rounded-full transition-all duration-300`}
                        style={{ width: `${progressPercent}%` }}
                    />
                </div>
            </div>

            {/* Explanation */}
            <p className="text-sm text-gray-600 dark:text-gray-400 mb-3">
                {result.explanation}
            </p>

            {/* Matched phrases */}
            {result.matches.length > 0 && (
                <div className="flex flex-wrap gap-1.5">
                    {result.matches.map((match, index) => (
                        <span
                            key={index}
                            className="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 text-xs font-mono px-2 py-0.5 rounded border border-gray-200 dark:border-gray-600"
                        >
                            {match}
                        </span>
                    ))}
                </div>
            )}
        </div>
    );
}
