import React from 'react';
import { Highlight } from '../types';

interface HighlightedTextProps {
    text: string;
    highlights: Highlight[];
}

interface Segment {
    text: string;
    highlight?: Highlight;
}

function buildSegments(text: string, highlights: Highlight[]): Segment[] {
    if (!highlights.length) {
        return [{ text }];
    }

    // Sort highlights by start position
    const sorted = [...highlights].sort((a, b) => a.start - b.start);
    const segments: Segment[] = [];
    let cursor = 0;

    for (const highlight of sorted) {
        const start = Math.max(highlight.start, cursor);
        const end = Math.min(highlight.end, text.length);

        if (start > cursor) {
            segments.push({ text: text.slice(cursor, start) });
        }

        if (end > start) {
            segments.push({
                text: text.slice(start, end),
                highlight,
            });
            cursor = end;
        }
    }

    if (cursor < text.length) {
        segments.push({ text: text.slice(cursor) });
    }

    return segments;
}

function buildLegend(highlights: Highlight[]): { category: string; colour: string }[] {
    const seen = new Map<string, string>();
    for (const h of highlights) {
        if (!seen.has(h.category)) {
            seen.set(h.category, h.colour);
        }
    }
    return Array.from(seen.entries()).map(([category, colour]) => ({ category, colour }));
}

export default function HighlightedText({ text, highlights }: HighlightedTextProps) {
    const segments = buildSegments(text, highlights);
    const legend = buildLegend(highlights);

    return (
        <div>
            {legend.length > 0 && (
                <div className="flex flex-wrap gap-2 mb-3">
                    {legend.map(({ category, colour }) => (
                        <span
                            key={category}
                            className={`${colour} text-xs font-medium px-2 py-0.5 rounded-full border border-black/10 dark:border-white/10`}
                        >
                            {category}
                        </span>
                    ))}
                </div>
            )}

            <pre className="whitespace-pre-wrap font-sans text-sm text-gray-800 dark:text-gray-200 leading-relaxed bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg p-4 overflow-auto">
                {segments.map((segment, index) => {
                    if (segment.highlight) {
                        return (
                            <mark
                                key={index}
                                className={`${segment.highlight.colour} rounded px-0.5`}
                                title={segment.highlight.category}
                            >
                                {segment.text}
                            </mark>
                        );
                    }
                    return <React.Fragment key={index}>{segment.text}</React.Fragment>;
                })}
            </pre>
        </div>
    );
}
