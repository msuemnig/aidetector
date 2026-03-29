export interface DetectorResult {
    category: string;
    score: number;
    cap: number;
    occurrences: number;
    matches: string[];
    explanation: string;
    confidence: 'HIGH' | 'MEDIUM' | 'LOW';
}

export interface LinguisticFactor {
    label: string;
    rawValue: number;
    displayValue: string;
    status: 'normal' | 'warning' | 'alert' | 'insufficient' | 'human_signal';
    explanation: string;
    confidence: 'HIGH' | 'MEDIUM' | 'LOW';
}

export interface Highlight {
    start: number;
    end: number;
    category: string;
    colour: string;
}

// --- Accuracy / Testing page types ---

export interface FixtureRow {
    id: string;
    source: string;
    label: 'human' | 'ai';
    topic: string;
    score: number;
    classification: string;
    colour: 'green' | 'yellow' | 'red';
    result: 'pass' | 'fail' | 'uncertain';
    topDetectors: { category: string; score: number; cap: number; occurrences: number; confidence: string }[];
    wordCount: number;
}

export interface SampleFixture extends FixtureRow {
    text: string;
    detectorResults: DetectorResult[];
    linguisticFactors: LinguisticFactor[];
}

export interface SourceSummary {
    total: number;
    pass: number;
    fail: number;
    uncertain: number;
    accuracy: number;
}

export interface AccuracyReport {
    rows: FixtureRow[];
    samples: SampleFixture[];
    summary: Record<string, SourceSummary>;
    overall: {
        total: number;
        pass: number;
        fail: number;
        uncertain: number;
        overallAccuracy: number;
        aiDetectionRate: number;
        humanPassRate: number;
        falsePositives: number;
        falseNegatives: number;
        truePositives: number;
        trueNegatives: number;
    };
    thresholds: { ai: number; human: number };
    generatedAt: string;
}

// --- Main analysis types ---

export interface AnalysisReport {
    text: string;
    wordCount: number;
    characterCount: number;
    averageWordLength: number;
    analysedAt: string;
    totalScore: number;
    classification: string;
    classificationColour: 'green' | 'yellow' | 'red';
    detectorResults: DetectorResult[];
    linguisticFactors: LinguisticFactor[];
    highlights: Highlight[];
}
