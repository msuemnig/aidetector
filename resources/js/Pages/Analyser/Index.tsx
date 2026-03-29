import React, { useMemo } from 'react';
import { Head, useForm } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';

interface IndexProps {
    errors: Partial<Record<string, string>>;
}

function countWords(text: string): number {
    const trimmed = text.trim();
    if (!trimmed) return 0;
    return trimmed.split(/\s+/).length;
}

export default function Index({ errors }: IndexProps) {
    const form = useForm({ text: '' });

    const charCount = form.data.text.length;
    const wordCount = useMemo(() => countWords(form.data.text), [form.data.text]);

    const MIN_WORDS = 300;
    const tooShort = wordCount > 0 && wordCount < MIN_WORDS;

    const charLimitWarning: 'orange' | 'red' | null =
        charCount > 50000 ? 'red' : charCount > 45000 ? 'orange' : null;

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        form.post(route('analyser.analyse'));
    };

    return (
        <AppLayout>
            <Head title="Analyse Text" />

            <div className="max-w-3xl mx-auto">
                <div className="mb-6">
                    <h2 className="text-2xl font-bold text-gray-900 dark:text-white mb-1">
                        Analyse Text
                    </h2>
                    <p className="text-sm text-gray-500 dark:text-gray-400">
                        Paste or type text below to check it for AI-generated writing patterns.
                    </p>
                </div>

                <form onSubmit={handleSubmit}>
                    <div className="mb-4">
                        <textarea
                            value={form.data.text}
                            onChange={(e) => form.setData('text', e.target.value)}
                            placeholder="Paste or type text here to analyse it for AI-generated patterns..."
                            rows={12}
                            className={[
                                'w-full min-h-[300px] px-4 py-3 rounded-lg border text-sm font-sans',
                                'text-gray-900 dark:text-white',
                                'bg-white dark:bg-gray-900',
                                'placeholder-gray-400 dark:placeholder-gray-600',
                                'resize-y transition-colors',
                                'focus:outline-none focus:ring-2',
                                errors.text
                                    ? 'border-red-400 dark:border-red-600 focus:ring-red-400'
                                    : charLimitWarning === 'red'
                                    ? 'border-red-400 dark:border-red-600 focus:ring-red-400'
                                    : charLimitWarning === 'orange'
                                    ? 'border-orange-400 dark:border-orange-500 focus:ring-orange-400'
                                    : tooShort
                                    ? 'border-amber-400 dark:border-amber-500 focus:ring-amber-400'
                                    : 'border-gray-300 dark:border-gray-600 focus:ring-blue-500',
                            ].join(' ')}
                        />

                        {/* Counts row */}
                        <div className="flex items-center justify-between mt-1.5 px-0.5">
                            <div className="flex items-center gap-3 text-xs">
                                <span className={tooShort ? 'text-amber-600 dark:text-amber-400 font-medium' : 'text-gray-500 dark:text-gray-400'}>
                                    {wordCount.toLocaleString()} / {MIN_WORDS} words minimum
                                </span>
                                <span
                                    className={
                                        charLimitWarning === 'red'
                                            ? 'text-red-600 dark:text-red-400 font-medium'
                                            : charLimitWarning === 'orange'
                                            ? 'text-orange-600 dark:text-orange-400 font-medium'
                                            : 'text-gray-500 dark:text-gray-400'
                                    }
                                >
                                    {charCount.toLocaleString()} / 50,000 characters
                                </span>
                            </div>

                            {charLimitWarning === 'red' && (
                                <span className="text-xs text-red-600 dark:text-red-400 font-medium">
                                    Character limit exceeded
                                </span>
                            )}
                            {charLimitWarning === 'orange' && (
                                <span className="text-xs text-orange-600 dark:text-orange-400 font-medium">
                                    Approaching character limit
                                </span>
                            )}
                            {tooShort && !charLimitWarning && (
                                <span className="text-xs text-amber-600 dark:text-amber-400 font-medium">
                                    Too short for reliable detection
                                </span>
                            )}
                        </div>

                        {/* Validation error */}
                        {errors.text && (
                            <p className="mt-1.5 text-sm text-red-600 dark:text-red-400">
                                {errors.text}
                            </p>
                        )}
                    </div>

                    <div className="flex items-center justify-end">
                        <button
                            type="submit"
                            disabled={!form.data.text.trim() || form.processing || charLimitWarning === 'red' || tooShort}
                            className="inline-flex items-center gap-2 px-6 py-2.5 bg-red-600 hover:bg-red-700 disabled:bg-gray-300 dark:disabled:bg-gray-700 disabled:cursor-not-allowed text-white font-semibold text-sm rounded-lg transition-colors shadow-sm"
                        >
                            {form.processing && (
                                <svg
                                    className="w-4 h-4 animate-spin"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <circle
                                        className="opacity-25"
                                        cx="12"
                                        cy="12"
                                        r="10"
                                        stroke="currentColor"
                                        strokeWidth="4"
                                    />
                                    <path
                                        className="opacity-75"
                                        fill="currentColor"
                                        d="M4 12a8 8 0 018-8v8H4z"
                                    />
                                </svg>
                            )}
                            {form.processing ? 'Analysing…' : 'Analyse Text'}
                        </button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
