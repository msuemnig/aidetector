import React from 'react';
import { LinguisticFactor } from '../types';

interface LinguisticFactorRowProps {
    factor: LinguisticFactor;
}

const statusStyles: Record<LinguisticFactor['status'], { badge: string; bar: string; label: string }> = {
    human_signal: {
        badge: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
        bar: 'bg-blue-500',
        label: 'Human signal',
    },
    normal: {
        badge: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
        bar: 'bg-green-500',
        label: 'Normal',
    },
    warning: {
        badge: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
        bar: 'bg-yellow-500',
        label: 'Warning',
    },
    alert: {
        badge: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
        bar: 'bg-red-500',
        label: 'Alert',
    },
    insufficient: {
        badge: 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400',
        bar: 'bg-gray-400',
        label: 'Insufficient data',
    },
};

export default function LinguisticFactorRow({ factor }: LinguisticFactorRowProps) {
    const styles = statusStyles[factor.status];
    const barWidth = Math.min(Math.max(factor.rawValue, 0), 100);

    return (
        <div className="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
            <div className="flex items-center justify-between gap-4 mb-2">
                <div className="flex items-center gap-2 min-w-0">
                    <span className="font-medium text-gray-900 dark:text-white text-sm truncate">
                        {factor.label}
                    </span>
                    <span className={`text-[10px] font-semibold px-1.5 py-0.5 rounded ${
                        factor.confidence === 'HIGH'   ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' :
                        factor.confidence === 'MEDIUM' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400' :
                        'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400'
                    }`}>
                        {factor.confidence}
                    </span>
                    <span className="font-mono text-sm text-gray-600 dark:text-gray-300 shrink-0">
                        {factor.displayValue}
                    </span>
                </div>
                <span
                    className={`${styles.badge} text-xs font-semibold px-2 py-0.5 rounded-full shrink-0`}
                >
                    {styles.label}
                </span>
            </div>

            <div className="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5 mb-2">
                <div
                    className={`${styles.bar} h-1.5 rounded-full transition-all duration-300`}
                    style={{ width: `${barWidth}%` }}
                />
            </div>

            <p className="text-sm text-gray-600 dark:text-gray-400">
                {factor.explanation}
            </p>
        </div>
    );
}
