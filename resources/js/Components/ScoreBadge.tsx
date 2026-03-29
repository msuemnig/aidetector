import React from 'react';

interface ScoreBadgeProps {
    score: number;
    classification: string;
    colour: 'green' | 'yellow' | 'red';
}

const colourMap = {
    green: {
        panel: 'bg-green-100 dark:bg-green-900 border border-green-300 dark:border-green-700',
        score: 'text-green-700 dark:text-green-300',
        label: 'text-green-800 dark:text-green-200',
        sub: 'text-green-600 dark:text-green-400',
        subText: 'No significant AI patterns detected',
    },
    yellow: {
        panel: 'bg-yellow-100 dark:bg-yellow-900 border border-yellow-300 dark:border-yellow-700',
        score: 'text-yellow-700 dark:text-yellow-300',
        label: 'text-yellow-800 dark:text-yellow-200',
        sub: 'text-yellow-600 dark:text-yellow-400',
        subText: 'Some AI writing patterns found',
    },
    red: {
        panel: 'bg-red-100 dark:bg-red-900 border border-red-300 dark:border-red-700',
        score: 'text-red-700 dark:text-red-300',
        label: 'text-red-800 dark:text-red-200',
        sub: 'text-red-600 dark:text-red-400',
        subText: 'Strong AI writing patterns detected',
    },
};

export default function ScoreBadge({ score, classification, colour }: ScoreBadgeProps) {
    const styles = colourMap[colour];

    return (
        <div className={`${styles.panel} rounded-2xl p-8 text-center`}>
            <div className={`text-8xl font-bold leading-none mb-3 ${styles.score}`}>
                {score}
            </div>
            <div className="text-xs font-semibold uppercase tracking-widest text-gray-400 dark:text-gray-500 mb-1">
                out of 100
            </div>
            <div className={`text-xl font-semibold mb-2 ${styles.label}`}>
                {classification}
            </div>
            <div className={`text-sm ${styles.sub}`}>
                {styles.subText}
            </div>
        </div>
    );
}
