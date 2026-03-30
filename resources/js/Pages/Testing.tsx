import React, { useState } from 'react';
import { Head } from '@inertiajs/react';
import AppLayout from '../Layouts/AppLayout';
import { AccuracyReport, FixtureRow, SampleFixture } from '../types';

interface TestingProps {
    report: AccuracyReport;
}

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

const SOURCE_LABELS: Record<string, string> = {
    human: 'Human (The Conversation)',
    'ai-claude': 'Claude (Anthropic)',
    'ai-gemini': 'Gemini (Google)',
    'ai-chatgpt': 'ChatGPT (OpenAI)',
};

const SOURCE_COLOURS: Record<string, string> = {
    human: 'bg-emerald-500',
    'ai-claude': 'bg-violet-500',
    'ai-gemini': 'bg-blue-500',
    'ai-chatgpt': 'bg-amber-500',
};

const SOURCE_TEXT_COLOURS: Record<string, string> = {
    human: 'text-emerald-600 dark:text-emerald-400',
    'ai-claude': 'text-violet-600 dark:text-violet-400',
    'ai-gemini': 'text-blue-600 dark:text-blue-400',
    'ai-chatgpt': 'text-amber-600 dark:text-amber-400',
};

function resultBadge(result: string) {
    const cls =
        result === 'pass'
            ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'
            : result === 'fail'
            ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'
            : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400';
    const label = result === 'pass' ? 'Pass' : result === 'fail' ? 'Fail' : 'Uncertain';
    return <span className={`inline-block px-2 py-0.5 rounded text-xs font-semibold ${cls}`}>{label}</span>;
}

function formatAnalysedAt(iso: string): string {
    return new Date(iso).toLocaleString(undefined, {
        day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit',
    });
}

// ---------------------------------------------------------------------------
// Stat card
// ---------------------------------------------------------------------------

function StatCard({ label, value, sub }: { label: string; value: string; sub?: string }) {
    return (
        <div className="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg p-5 text-center">
            <div className="text-2xl font-bold text-gray-900 dark:text-white">{value}</div>
            <div className="text-sm text-gray-500 dark:text-gray-400 mt-1">{label}</div>
            {sub && <div className="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{sub}</div>}
        </div>
    );
}

// ---------------------------------------------------------------------------
// Threshold diagram
// ---------------------------------------------------------------------------

function ThresholdDiagram({ ai, human }: { ai: number; human: number }) {
    return (
        <div className="mt-4">
            <div className="flex h-8 rounded-lg overflow-hidden text-xs font-semibold">
                <div className="bg-green-500/80 text-white flex items-center justify-center" style={{ width: `${human}%` }}>
                    0–{human - 1}
                </div>
                <div className="bg-yellow-400/80 text-gray-900 flex items-center justify-center" style={{ width: `${ai - human}%` }}>
                    {human}–{ai - 1}
                </div>
                <div className="bg-red-500/80 text-white flex items-center justify-center" style={{ width: `${100 - ai}%` }}>
                    {ai}–100
                </div>
            </div>
            <div className="flex justify-between text-xs text-gray-500 dark:text-gray-400 mt-1.5 px-0.5">
                <span>Likely Human</span>
                <span>Uncertain</span>
                <span>Likely AI</span>
            </div>
        </div>
    );
}

// ---------------------------------------------------------------------------
// Score distribution chart (pure CSS)
// ---------------------------------------------------------------------------

function ScoreDistribution({ rows }: { rows: FixtureRow[] }) {
    const sources = ['human', 'ai-claude', 'ai-gemini', 'ai-chatgpt'];
    const maxScore = Math.max(...rows.map((r) => r.score), 1);

    return (
        <div className="space-y-4">
            {sources.map((src) => {
                const srcRows = rows.filter((r) => r.source === src).sort((a, b) => a.score - b.score);
                if (srcRows.length === 0) return null;
                return (
                    <div key={src}>
                        <div className={`text-sm font-semibold mb-1.5 ${SOURCE_TEXT_COLOURS[src] ?? 'text-gray-700 dark:text-gray-300'}`}>
                            {SOURCE_LABELS[src] ?? src}
                        </div>
                        <div className="flex items-end gap-1.5 h-16">
                            {srcRows.map((row, i) => (
                                <div key={i} className="flex-1 flex flex-col items-center justify-end h-full" title={`${row.topic}: ${row.score}`}>
                                    <div className="text-[10px] text-gray-500 dark:text-gray-400 mb-0.5">{row.score}</div>
                                    <div
                                        className={`w-full rounded-t ${SOURCE_COLOURS[src] ?? 'bg-gray-400'} transition-all`}
                                        style={{ height: `${Math.max((row.score / 100) * 100, 4)}%` }}
                                    />
                                </div>
                            ))}
                        </div>
                        <div className="flex gap-1.5 mt-0.5">
                            {srcRows.map((_, i) => (
                                <div key={i} className="flex-1 h-px bg-gray-200 dark:bg-gray-700" />
                            ))}
                        </div>
                    </div>
                );
            })}
            {/* Threshold lines legend */}
            <div className="flex items-center gap-4 text-xs text-gray-500 dark:text-gray-400 mt-2 pt-2 border-t border-gray-200 dark:border-gray-700">
                <span>Score scale: 0 (human) to 100 (AI)</span>
                <span className="text-green-600 dark:text-green-400">Below 30 = Human</span>
                <span className="text-yellow-600 dark:text-yellow-400">30–44 = Uncertain</span>
                <span className="text-red-600 dark:text-red-400">45+ = AI</span>
            </div>
        </div>
    );
}

// ---------------------------------------------------------------------------
// Accuracy table with inline bars
// ---------------------------------------------------------------------------

function AccuracyTable({ summary }: { summary: AccuracyReport['summary'] }) {
    const sources = ['human', 'ai-claude', 'ai-gemini', 'ai-chatgpt'];

    return (
        <div className="overflow-x-auto">
            <table className="w-full text-sm">
                <thead>
                    <tr className="text-left text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">
                        <th className="pb-2 font-medium">Source</th>
                        <th className="pb-2 font-medium text-center">Total</th>
                        <th className="pb-2 font-medium text-center">Pass</th>
                        <th className="pb-2 font-medium text-center">Fail</th>
                        <th className="pb-2 font-medium text-center">Uncertain</th>
                        <th className="pb-2 font-medium text-right">Accuracy</th>
                        <th className="pb-2 font-medium pl-4" style={{ minWidth: 120 }}>Distribution</th>
                    </tr>
                </thead>
                <tbody>
                    {sources.map((src) => {
                        const s = summary[src];
                        if (!s || s.total === 0) return null;
                        const pPct = (s.pass / s.total) * 100;
                        const fPct = (s.fail / s.total) * 100;
                        const uPct = (s.uncertain / s.total) * 100;
                        return (
                            <tr key={src} className="border-b border-gray-100 dark:border-gray-800">
                                <td className={`py-2.5 font-medium ${SOURCE_TEXT_COLOURS[src]}`}>{SOURCE_LABELS[src]}</td>
                                <td className="py-2.5 text-center text-gray-700 dark:text-gray-300">{s.total}</td>
                                <td className="py-2.5 text-center text-green-600 dark:text-green-400 font-semibold">{s.pass}</td>
                                <td className="py-2.5 text-center text-red-600 dark:text-red-400 font-semibold">{s.fail}</td>
                                <td className="py-2.5 text-center text-yellow-600 dark:text-yellow-400 font-semibold">{s.uncertain}</td>
                                <td className="py-2.5 text-right font-bold text-gray-900 dark:text-white">{s.accuracy}%</td>
                                <td className="py-2.5 pl-4">
                                    <div className="flex h-3 rounded overflow-hidden">
                                        {pPct > 0 && <div className="bg-green-500" style={{ width: `${pPct}%` }} />}
                                        {uPct > 0 && <div className="bg-yellow-400" style={{ width: `${uPct}%` }} />}
                                        {fPct > 0 && <div className="bg-red-500" style={{ width: `${fPct}%` }} />}
                                    </div>
                                </td>
                            </tr>
                        );
                    })}
                </tbody>
            </table>
        </div>
    );
}

// ---------------------------------------------------------------------------
// Confusion matrix
// ---------------------------------------------------------------------------

function ConfusionMatrix({ overall }: { overall: AccuracyReport['overall'] }) {
    return (
        <div>
            <div className="grid grid-cols-[auto_1fr_1fr] gap-0 text-sm">
                {/* Header row */}
                <div />
                <div className="text-center font-semibold text-gray-500 dark:text-gray-400 pb-2">Predicted Human</div>
                <div className="text-center font-semibold text-gray-500 dark:text-gray-400 pb-2">Predicted AI</div>
                {/* Actual Human row */}
                <div className="font-semibold text-gray-500 dark:text-gray-400 pr-3 flex items-center">Actual Human</div>
                <div className="bg-green-100 dark:bg-green-900/30 border border-green-300 dark:border-green-700 rounded-tl-lg p-4 text-center">
                    <div className="text-2xl font-bold text-green-700 dark:text-green-400">{overall.trueNegatives}</div>
                    <div className="text-xs text-green-600 dark:text-green-500 mt-1">True Negatives</div>
                </div>
                <div className="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-tr-lg p-4 text-center">
                    <div className="text-2xl font-bold text-red-700 dark:text-red-400">{overall.falsePositives}</div>
                    <div className="text-xs text-red-600 dark:text-red-500 mt-1">False Positives</div>
                </div>
                {/* Actual AI row */}
                <div className="font-semibold text-gray-500 dark:text-gray-400 pr-3 flex items-center">Actual AI</div>
                <div className="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-bl-lg p-4 text-center">
                    <div className="text-2xl font-bold text-red-700 dark:text-red-400">{overall.falseNegatives}</div>
                    <div className="text-xs text-red-600 dark:text-red-500 mt-1">False Negatives</div>
                </div>
                <div className="bg-green-100 dark:bg-green-900/30 border border-green-300 dark:border-green-700 rounded-br-lg p-4 text-center">
                    <div className="text-2xl font-bold text-green-700 dark:text-green-400">{overall.truePositives}</div>
                    <div className="text-xs text-green-600 dark:text-green-500 mt-1">True Positives</div>
                </div>
            </div>
            <p className="text-xs text-gray-500 dark:text-gray-400 mt-3">
                Note: {overall.uncertain} samples fell in the uncertain zone (score 30–59) and are counted as failures in the accuracy metric.
            </p>
        </div>
    );
}

// ---------------------------------------------------------------------------
// Per-sample results table
// ---------------------------------------------------------------------------

function SampleTable({ rows }: { rows: FixtureRow[] }) {
    const [sortBy, setSortBy] = useState<'source' | 'score'>('source');
    const sorted = [...rows].sort((a, b) =>
        sortBy === 'score' ? b.score - a.score : 0, // default order from backend is by source
    );

    return (
        <div>
            <div className="flex items-center gap-3 mb-3">
                <span className="text-xs text-gray-500 dark:text-gray-400">Sort by:</span>
                <button onClick={() => setSortBy('source')} className={`text-xs px-2 py-1 rounded ${sortBy === 'source' ? 'bg-gray-200 dark:bg-gray-700 font-semibold text-gray-900 dark:text-white' : 'text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800'}`}>
                    Source
                </button>
                <button onClick={() => setSortBy('score')} className={`text-xs px-2 py-1 rounded ${sortBy === 'score' ? 'bg-gray-200 dark:bg-gray-700 font-semibold text-gray-900 dark:text-white' : 'text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800'}`}>
                    Score
                </button>
            </div>
            <div className="overflow-x-auto">
                <table className="w-full text-sm">
                    <thead>
                        <tr className="text-left text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">
                            <th className="pb-2 font-medium">Source</th>
                            <th className="pb-2 font-medium">Topic</th>
                            <th className="pb-2 font-medium text-center">Words</th>
                            <th className="pb-2 font-medium text-center">Score</th>
                            <th className="pb-2 font-medium text-center">Result</th>
                            <th className="pb-2 font-medium">Top Detectors</th>
                        </tr>
                    </thead>
                    <tbody>
                        {sorted.map((row) => (
                            <tr key={row.id} className="border-b border-gray-100 dark:border-gray-800">
                                <td className={`py-2 font-medium text-xs ${SOURCE_TEXT_COLOURS[row.source]}`}>
                                    {SOURCE_LABELS[row.source]?.split(' ')[0] ?? row.source}
                                </td>
                                <td className="py-2 text-gray-700 dark:text-gray-300 text-xs max-w-[200px] truncate">{row.topic}</td>
                                <td className="py-2 text-center text-gray-500 dark:text-gray-400 text-xs">{row.wordCount}</td>
                                <td className="py-2 text-center font-bold text-gray-900 dark:text-white">{row.score}</td>
                                <td className="py-2 text-center">{resultBadge(row.result)}</td>
                                <td className="py-2 text-xs text-gray-500 dark:text-gray-400">
                                    {row.topDetectors.slice(0, 3).map((d, i) => (
                                        <span key={i}>
                                            {i > 0 && ', '}
                                            {d.category} ({d.score})
                                        </span>
                                    ))}
                                    {row.topDetectors.length === 0 && <span className="italic">none</span>}
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </div>
    );
}

// ---------------------------------------------------------------------------
// Sample deep dives
// ---------------------------------------------------------------------------

function SampleDeepDive({ sample }: { sample: SampleFixture }) {
    const [open, setOpen] = useState(false);

    return (
        <div className="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
            <button onClick={() => setOpen(!open)} className="w-full px-4 py-3 flex items-center justify-between text-left hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                <div className="flex items-center gap-3">
                    {resultBadge(sample.result)}
                    <span className={`text-sm font-semibold ${SOURCE_TEXT_COLOURS[sample.source]}`}>
                        {SOURCE_LABELS[sample.source]?.split('(')[0]?.trim() ?? sample.source}
                    </span>
                    <span className="text-sm text-gray-500 dark:text-gray-400">
                        Score: <strong className="text-gray-900 dark:text-white">{sample.score}</strong>
                    </span>
                    <span className="text-xs text-gray-400 dark:text-gray-500">{sample.topic}</span>
                </div>
                <svg className={`w-4 h-4 text-gray-400 transition-transform ${open ? 'rotate-180' : ''}`} fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                    <path d="M19 9l-7 7-7-7" />
                </svg>
            </button>
            {open && (
                <div className="px-4 pb-4 space-y-4 border-t border-gray-200 dark:border-gray-700">
                    {/* Text preview */}
                    <div className="mt-3 bg-gray-50 dark:bg-gray-800 rounded p-3 text-sm text-gray-700 dark:text-gray-300 leading-relaxed max-h-48 overflow-y-auto">
                        {sample.text}
                    </div>
                    {/* Linguistic factors */}
                    <div>
                        <h4 className="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Linguistic Factors</h4>
                        <div className="grid grid-cols-2 sm:grid-cols-4 gap-2">
                            {sample.linguisticFactors.map((f) => (
                                <div key={f.label} className="bg-gray-50 dark:bg-gray-800 rounded p-2 text-xs">
                                    <div className="font-medium text-gray-700 dark:text-gray-300 truncate">{f.label}</div>
                                    <div className="flex items-center gap-1.5 mt-1">
                                        <span className={
                                            f.status === 'alert' ? 'text-red-600 dark:text-red-400 font-semibold'
                                            : f.status === 'warning' ? 'text-yellow-600 dark:text-yellow-400 font-semibold'
                                            : f.status === 'human_signal' ? 'text-blue-600 dark:text-blue-400 font-semibold'
                                            : 'text-gray-500 dark:text-gray-400'
                                        }>
                                            {f.status}
                                        </span>
                                        <span className="text-gray-400 dark:text-gray-500">{f.displayValue}</span>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                    {/* Detector results */}
                    <div>
                        <h4 className="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Pattern Detectors</h4>
                        <div className="space-y-1.5">
                            {sample.detectorResults.filter((d) => d.score > 0).map((d) => (
                                <div key={d.category} className="flex items-center gap-2 text-xs">
                                    <div className="w-32 font-medium text-gray-700 dark:text-gray-300 truncate">{d.category}</div>
                                    <div className="flex-1 h-2 bg-gray-100 dark:bg-gray-800 rounded overflow-hidden">
                                        <div className="h-full bg-red-500 rounded" style={{ width: `${(d.score / d.cap) * 100}%` }} />
                                    </div>
                                    <div className="text-gray-500 dark:text-gray-400 w-12 text-right">{d.score}/{d.cap}</div>
                                </div>
                            ))}
                            {sample.detectorResults.filter((d) => d.score > 0).length === 0 && (
                                <div className="text-xs text-gray-400 italic">No pattern detectors fired</div>
                            )}
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}

// ---------------------------------------------------------------------------
// Main page
// ---------------------------------------------------------------------------

export default function Testing({ report }: TestingProps) {
    const { rows, samples, summary, overall, thresholds } = report;

    return (
        <AppLayout>
            <Head title="Testing & Results" />

            <div className="max-w-5xl mx-auto space-y-10">

                {/* --- Section 1: Overview --- */}
                <div>
                    <h2 className="text-2xl font-bold text-gray-900 dark:text-white mb-1">Testing & Results</h2>
                    <p className="text-sm text-gray-500 dark:text-gray-400 mb-6">
                        Empirical accuracy evaluation of the AI text detection heuristics &middot; Generated {formatAnalysedAt(report.generatedAt)}
                    </p>

                    <div className="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
                        <StatCard label="Total Samples" value={String(overall.total)} sub={`${rows.filter(r => r.label === 'ai').length} AI + ${rows.filter(r => r.label === 'human').length} human`} />
                        <StatCard label="Overall Accuracy" value={`${overall.overallAccuracy}%`} sub={`${overall.pass} correct of ${overall.total}`} />
                        <StatCard label="Human Pass Rate" value={`${overall.humanPassRate}%`} sub={`Score < ${thresholds.human}`} />
                        <StatCard label="AI Detection Rate" value={`${overall.aiDetectionRate}%`} sub={`Score >= ${thresholds.ai}`} />
                    </div>

                    <div className="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 text-sm text-blue-900 dark:text-blue-200 leading-relaxed">
                        <strong>Empirical validation:</strong> The detector is calibrated against <strong>136 fixture texts</strong> spanning <strong>7 genres</strong>. The core controlled experiment uses COP21 climate agreement texts &mdash; human articles from <em>The Conversation</em> (academic journalism, published Nov&ndash;Dec 2015) and AI samples generated by Claude, Gemini, and ChatGPT with the same prompt requesting academic-style essays on 10 COP21 sub-topics. This shared-topic design eliminates domain vocabulary as a confounding variable, isolating writing style as the differentiator.
                    </div>
                </div>

                {/* --- Section 2: Methodology --- */}
                <div>
                    <h3 className="text-lg font-bold text-gray-900 dark:text-white mb-4">Methodology</h3>

                    <div className="grid sm:grid-cols-2 gap-4 mb-6">
                        <div className="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                            <h4 className="font-semibold text-gray-900 dark:text-white mb-2">Why COP21?</h4>
                            <p className="text-sm text-gray-600 dark:text-gray-400 leading-relaxed">
                                Using a single shared topic (the 2015 Paris climate agreement) means both human and AI authors draw from the same domain vocabulary. This isolates <em>writing style</em> as the differentiator, rather than subject matter. All 10 sub-topics (temperature targets, enforcement gap, business reaction, etc.) are covered by every source.
                            </p>
                        </div>
                        <div className="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                            <h4 className="font-semibold text-gray-900 dark:text-white mb-2">Sources</h4>
                            <ul className="text-sm text-gray-600 dark:text-gray-400 space-y-1.5">
                                <li><span className="text-emerald-600 dark:text-emerald-400 font-semibold">Human:</span> 10 excerpts from <em>The Conversation</em>, published Nov&ndash;Dec 2015 by university academics</li>
                                <li><span className="text-violet-600 dark:text-violet-400 font-semibold">Claude:</span> 10 essays (~420 words each), academic prose style</li>
                                <li><span className="text-blue-600 dark:text-blue-400 font-semibold">Gemini:</span> 10 essays (~415 words each), academic prose style</li>
                                <li><span className="text-amber-600 dark:text-amber-400 font-semibold">ChatGPT:</span> 10 essays (~273 words each), academic prose style</li>
                            </ul>
                        </div>
                    </div>

                    <div className="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                        <h4 className="font-semibold text-gray-900 dark:text-white mb-2">Score Thresholds</h4>
                        <p className="text-sm text-gray-600 dark:text-gray-400 mb-2">
                            Each sample is run through 8 active pattern detectors and 9 active linguistic analysers. The weighted sum produces a score from 0&ndash;100. Scores below 30 indicate likely human writing; scores of 45 or above indicate likely AI-generated text; scores of 30&ndash;44 fall in the uncertain zone.
                        </p>
                        <ThresholdDiagram ai={thresholds.ai} human={thresholds.human} />
                    </div>
                </div>

                {/* --- Section 3: Results Dashboard --- */}
                <div>
                    <h3 className="text-lg font-bold text-gray-900 dark:text-white mb-4">Results Dashboard</h3>

                    <div className="space-y-6">
                        {/* Score distribution */}
                        <div className="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg p-5">
                            <h4 className="font-semibold text-gray-900 dark:text-white mb-4">Score Distribution</h4>
                            <ScoreDistribution rows={rows} />
                        </div>

                        {/* Accuracy table + confusion matrix side by side */}
                        <div className="grid lg:grid-cols-3 gap-6">
                            <div className="lg:col-span-2 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg p-5">
                                <h4 className="font-semibold text-gray-900 dark:text-white mb-4">Accuracy by Source</h4>
                                <AccuracyTable summary={summary} />
                            </div>
                            <div className="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg p-5">
                                <h4 className="font-semibold text-gray-900 dark:text-white mb-4">Confusion Matrix</h4>
                                <ConfusionMatrix overall={overall} />
                            </div>
                        </div>
                    </div>
                </div>

                {/* --- Section 4: Per-sample results --- */}
                <div>
                    <h3 className="text-lg font-bold text-gray-900 dark:text-white mb-4">All {overall.total} Samples</h3>
                    <div className="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg p-5">
                        <SampleTable rows={rows} />
                    </div>
                </div>

                {/* --- Section 5: Sample deep dives --- */}
                {samples.length > 0 && (
                    <div>
                        <h3 className="text-lg font-bold text-gray-900 dark:text-white mb-2">Sample Deep Dives</h3>
                        <p className="text-sm text-gray-500 dark:text-gray-400 mb-4">
                            Selected samples showing full detector and linguistic analysis. Click to expand.
                        </p>
                        <div className="space-y-3">
                            {samples.map((sample) => (
                                <SampleDeepDive key={sample.id} sample={sample} />
                            ))}
                        </div>
                    </div>
                )}

                {/* --- Section 6: Caveats --- */}
                <div className="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-4">
                    <h4 className="font-semibold text-amber-900 dark:text-amber-200 mb-2">Caveats & Limitations</h4>
                    <ul className="text-sm text-amber-800 dark:text-amber-300 space-y-1.5 leading-relaxed list-disc list-inside">
                        <li><strong>Current accuracy:</strong> 52.9% overall accuracy, with 40.2% AI detection rate and 91.2% human pass rate. The system is conservative — it strongly avoids false positives on human text at the cost of missing some AI text.</li>
                        <li><strong>Single topic (core test):</strong> The core controlled experiment uses COP21 texts. The broader 136-fixture corpus spans 7 genres, but accuracy may still differ on domains not represented.</li>
                        <li><strong>ChatGPT length:</strong> ChatGPT essays averaged ~273 words despite a 400&ndash;500 word prompt, making them shorter than Claude and Gemini samples. This may affect comparability.</li>
                        <li><strong>Heuristic approach:</strong> This tool uses statistical pattern matching, not neural AI detection. It identifies writing-style signals, not the underlying model. False positives and negatives are expected.</li>
                        <li><strong>Rapid obsolescence:</strong> Research older than ~12 months proved unreliable for calibration. Many features reported as strong discriminators in published studies showed no discrimination in our 2026 corpus. LLM writing style evolves faster than the publication cycle.</li>
                    </ul>
                </div>
            </div>
        </AppLayout>
    );
}
