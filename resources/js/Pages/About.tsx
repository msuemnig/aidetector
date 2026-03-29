import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';

// ─── Types ───────────────────────────────────────────────────────────────────

interface Citation {
    authors: string;
    year: string;
    title: string;
    venue: string;
    url: string;
    note?: string;
}

interface Detector {
    name: string;
    confidence: 'HIGH' | 'MEDIUM' | 'LOW';
    description: string;
    rationale: string;
    caveats?: string;
    citations: Citation[];
}

// ─── Citation data ────────────────────────────────────────────────────────────

const CITATIONS: Record<string, Citation> = {
    kobak2025: {
        authors: 'Kobak, D., González-Márquez, R., Horvát, E.-Á., & Lause, J.',
        year: '2025',
        title: 'Delving into LLM-assisted writing in biomedical publications through excess vocabulary',
        venue: 'Science Advances, 11(27), eadt3813',
        url: 'https://doi.org/10.1126/sciadv.adt3813',
    },
    geng2025: {
        authors: 'Geng, M., & Trotta, R.',
        year: '2025',
        title: 'Human-LLM Coevolution: Evidence from Academic Writing',
        venue: 'arXiv:2502.09606',
        url: 'https://arxiv.org/abs/2502.09606',
    },
    reinhart2025: {
        authors: 'Reinhart, A., Markey, B., Laudenbach, M., Pantusen, K., Yurko, R., Weinberg, G., & Brown, D.W.',
        year: '2025',
        title: 'Do LLMs write like humans? Variation in grammatical and rhetorical styles',
        venue: 'Proceedings of the National Academy of Sciences, 122, e2422455122',
        url: 'https://doi.org/10.1073/pnas.2422455122',
    },
    wu2025: {
        authors: 'Wu, J., Yang, S., Zhan, R., Yuan, Y., Chao, L.S., & Wong, D.F.',
        year: '2025',
        title: 'A Survey on LLM-Generated Text Detection: Necessity, Methods, and Future Directions',
        venue: 'Computational Linguistics (MIT Press), 51(1), 275–338',
        url: 'https://direct.mit.edu/coli/article/51/1/275/127462/',
    },
    peters2025: {
        authors: 'Peters, U., & Chin-Yee, B.',
        year: '2025',
        title: 'Generalization bias in large language model summarization of scientific research',
        venue: 'Royal Society Open Science, 12(4), 241776',
        url: 'https://doi.org/10.1098/rsos.241776',
    },
    munoz2024: {
        authors: 'Muñoz-Ortiz, A., Gómez-Rodríguez, C., & Vilares, D.',
        year: '2024',
        title: 'Contrasting linguistic patterns in human and LLM-generated news text',
        venue: 'Artificial Intelligence Review, 57(10), 265',
        url: 'https://doi.org/10.1007/s10462-024-10903-2',
    },
    reviriego2024: {
        authors: 'Reviriego, P., Conde, J., Merino-Gómez, E., Martínez, G., & Hernández, J.A.',
        year: '2024',
        title: 'Playing with words: Comparing the vocabulary and lexical diversity of ChatGPT and humans',
        venue: 'Machine Learning with Applications, 18, 100602',
        url: 'https://doi.org/10.1016/j.mlwa.2024.100602',
    },
    tercon2025: {
        authors: 'Terčon, L., & Dobrovoljc, K.',
        year: '2025',
        title: 'Linguistic Characteristics of AI-Generated Text: A Survey',
        venue: 'arXiv:2510.05136',
        url: 'https://arxiv.org/abs/2510.05136',
    },
    fredrick2025: {
        authors: 'Fredrick, D.R., & Craven, L.',
        year: '2025',
        title: 'Lexical diversity, syntactic complexity, and readability: a corpus-based analysis of ChatGPT and L2 student essays',
        venue: 'Frontiers in Education, 10, 1616935',
        url: 'https://doi.org/10.3389/feduc.2025.1616935',
    },
    mikhaylovskiy2025: {
        authors: 'Mikhaylovskiy, N.',
        year: '2025',
        title: "Zipf's and Heaps' Laws for Tokens and LLM-generated Texts",
        venue: 'Findings of ACL: EMNLP 2025, pp. 15469–15481',
        url: 'https://aclanthology.org/2025.findings-emnlp.837/',
    },
    mekala2024: {
        authors: 'Mekala, D. et al.',
        year: '2024',
        title: 'Detecting AI-Generated Text with Pre-Trained Models Using Linguistic Features',
        venue: 'Proceedings of ICON 2024, 21st International Conference on NLP',
        url: 'https://aclanthology.org/2024.icon-1.21/',
    },
    pudasaini2026: {
        authors: 'Pudasaini, S., Miralles-Pechuán, L., Lillis, D., & Llorens Salvador, M.',
        year: '2026',
        title: 'Why AI-Generated Text Detection Fails: Evidence from Explainable AI Beyond Benchmark Accuracy',
        venue: 'arXiv:2603.23146',
        url: 'https://arxiv.org/abs/2603.23146',
    },
    gorrie2024: {
        authors: 'Gorrie, C.',
        year: '2024',
        title: 'Why ChatGPT writes like that',
        venue: 'Dead Language Society (Substack)',
        url: 'https://www.deadlanguagesociety.com/p/rhetorical-analysis-ai',
        note: 'Practitioner source — linguistic PhD analysis',
    },
    sage2025: {
        authors: 'SAGE Publishing',
        year: '2025',
        title: 'AI detection for peer reviewers: Look out for red flags',
        venue: 'SAGE Perspectives',
        url: 'https://www.sagepub.com/explore-our-content/blogs/posts/sage-perspectives/2025/06/11/ai-detection-for-peer-reviewers-look-out-for-red-flags',
        note: 'Practitioner guidance from a major academic publisher',
    },
    wikipedia_ai: {
        authors: 'Wikipedia community',
        year: '2025',
        title: 'Signs of AI Writing',
        venue: 'Wikipedia (community-maintained, continuously updated)',
        url: 'https://en.wikipedia.org/wiki/Wikipedia:Signs_of_AI_writing',
        note: 'Community-curated field guide, not peer-reviewed',
    },
    norvig: {
        authors: 'Norvig, P.',
        year: '2012',
        title: 'Natural Language Corpus Data: Beautiful Data (word frequency list)',
        venue: 'norvig.com/ngrams — derived from Google Web Corpus (~1 trillion words)',
        url: 'http://norvig.com/ngrams/',
        note: 'Companion to: Norvig, P. (2009). Natural Language Corpus Data. In Segaran & Hammerbacher (Eds.), Beautiful Data. O\'Reilly.',
    },
};

// ─── Detector definitions ────────────────────────────────────────────────────

const PATTERN_DETECTORS: Detector[] = [
    {
        name: 'AI Vocabulary',
        confidence: 'HIGH',
        description: 'Detects terms statistically overrepresented in LLM-generated text: "delve into", "robust", "paradigm", "synergy", "holistic", "nuanced", "pivotal", "leverage", "stakeholders", and similar.',
        rationale: 'Kobak et al. (2025) analysed over 15 million PubMed abstracts and identified 379 "excess vocabulary" words whose frequency spiked anomalously after ChatGPT\'s release. Geng & Trotta (2025) tracked the same effect across 1.29 million arXiv abstracts, finding that once markers like "delve" were publicly identified in early 2024, their frequencies dropped — evidence of human editing of LLM output.',
        caveats: 'Individual terms decay in reliability once publicly identified. The detector requires multiple co-occurrences for a high score.',
        citations: [CITATIONS.kobak2025, CITATIONS.geng2025],
    },
    {
        name: 'Rule of Three',
        confidence: 'MEDIUM',
        description: 'Detects comma-separated triadic lists: "X, Y, and Z" constructions used at abnormally high frequency.',
        rationale: 'Reinhart et al. (2025) applied Biber\'s 66-feature linguistic tagset to LLM output and found systematic overuse of parallel constructions and list structures vs human text. Gorrie (2024), in a linguistic PhD-level analysis, specifically identifies the tricolon (rule of three) as among the most mechanically overused rhetorical devices in LLM prose — produced reflexively because training data rewarded tidy three-part enumerations.',
        caveats: 'No study has corpus-quantified this specific construction in isolation. Evidence is empirical (Reinhart) plus expert practitioner observation (Gorrie). Humans use triadic lists too; frequency is the signal, not presence.',
        citations: [CITATIONS.reinhart2025, CITATIONS.gorrie2024],
    },
    {
        name: 'Negative Parallelism',
        confidence: 'MEDIUM',
        description: 'Detects formal parallel constructions: "not only ... but also", "neither ... nor", "both ... and".',
        rationale: 'Reinhart et al. (2025) documents that instruction-tuned LLMs overuse rhetorical parallel and antithetical structures at rates far exceeding human writing. Gorrie (2024) specifically names antithesis ("not X, but Y") as an LLM favourite: "a device that tends to produce memorable lines, which is why it\'s so jarring when it appears in every other paragraph."',
        caveats: 'No paper isolates negative-parallelism constructions as a standalone measurable category. Support is from broader parallel-structure empirical work plus expert observation.',
        citations: [CITATIONS.reinhart2025, CITATIONS.gorrie2024],
    },
    {
        name: 'Outline & Conclusion',
        confidence: 'MEDIUM',
        description: 'Detects formulaic closing openers ("In conclusion,", "To summarise,", "Ultimately,") and the "Despite [X], [subject] still..." contrast-then-resolve pattern.',
        rationale: 'Wu et al. (2025), the field\'s most comprehensive peer-reviewed detection survey, identifies formulaic syntactic constructions and predictable discourse structuring as documented LLM linguistic fingerprints. The Wikipedia Signs of AI Writing guide (continuously updated through 2025) explicitly documents the "Despite challenges, [subject] persists" formula and formulaic closing sections based on extensive editorial observation.',
        caveats: 'No paper has corpus-quantified "In conclusion," frequency in isolation. Support rests on the broader structural-overuse finding and community documentation.',
        citations: [CITATIONS.wu2025, CITATIONS.wikipedia_ai],
    },
    {
        name: 'False Range',
        confidence: 'LOW',
        description: 'Detects abstract "from X to Y" constructions where neither X nor Y is a number, unit, or scalar quantity — e.g. "from innovation to implementation".',
        rationale: 'This is a practitioner-observed pattern documented in the Wikipedia Signs of AI Writing guide. No dedicated empirical study has independently corpus-tested this construction. It is included as a weak signal; the underlying tendency (LLM preference for abstract rhetorical phrasing structures) is consistent with Reinhart et al.\'s finding of a "noun-heavy, informationally dense" AI writing style.',
        caveats: 'LOW confidence. This heuristic rests on community observation only. Treat any score contribution from this detector as suggestive rather than evidential.',
        citations: [CITATIONS.wikipedia_ai, CITATIONS.reinhart2025],
    },
    {
        name: 'Vague Attribution',
        confidence: 'MEDIUM',
        description: 'Detects claims attributed to unnamed authorities: "studies show", "experts agree", "research suggests" — but only when no specific year, author, or publication is cited in the same sentence.',
        rationale: 'SAGE Publishing\'s June 2025 guidance for academic peer reviewers explicitly lists \'Studies have shown… without citing real studies\' as a primary red flag for AI-generated submissions. Peters & Chin-Yee (2025) provide the mechanism: across 4,900 LLM-generated science summaries, models overgeneralised in 26–73% of cases, converting specific findings into unqualified pronouncements — LLM summaries were nearly five times more likely to contain broad generalisations than matched human expert summaries.',
        caveats: 'The exclusion logic (skipping sentences that contain a four-digit year or "study by") was designed to avoid false positives on legitimate citations. However, modern LLMs now increasingly fabricate specific citations (GPT-4o: 19.9% fabrication rate per EurekAlert 2025), meaning a fake-but-specific citation will bypass the detector. Human writers in journalism, policy, and popular science also use vague attribution regularly.',
        citations: [CITATIONS.sage2025, CITATIONS.peters2025],
    },
    {
        name: 'Superficial Analysis',
        confidence: 'MEDIUM',
        description: 'Detects hedging filler phrases that signal the appearance of analysis without analytical content: "it is worth noting", "one could argue", "needless to say", "it goes without saying".',
        rationale: 'SAGE (2025) describes "Flawless but Empty Prose" — grammatically correct text that constitutes "words stitched together that have no meaning" — as a primary AI tell. Pudasaini et al. (2026) used 30 linguistic features including hedging and epistemic phrases for detection, achieving F1=0.9734 on benchmark classification tasks, confirming this feature class has high discriminative power.',
        caveats: 'Individual phrases in this list also appear in legitimate human academic writing. The signal is stronger when multiple phrases co-occur or appear in a text that already scores highly on other detectors.',
        citations: [CITATIONS.sage2025, CITATIONS.pudasaini2026],
    },
    {
        name: 'Overgeneralisation',
        confidence: 'HIGH',
        description: 'Detects sweeping universal claims: "everyone knows", "universal consensus", "it is well established", "most people would agree", "undeniably".',
        rationale: 'Peters & Chin-Yee (2025) provide the strongest direct evidence: across 4,900 LLM-generated science summaries, models overgeneralised in 26–73% of cases. LLM summaries were nearly five times more likely to contain broad generalisations than human expert (NEJM Journal Watch) summaries of the same studies. The mechanism is well understood: RLHF training optimises for confident-sounding answers, which incentivises universal claims over careful hedging.',
        caveats: 'The HIGH confidence rating applies to the general overgeneralisation tendency, not to the specific surface phrases. No corpus study has individually measured "everyone knows" vs baseline human frequency.',
        citations: [CITATIONS.peters2025],
    },
    {
        name: 'Undue Emphasis',
        confidence: 'MEDIUM',
        description: 'Detects gratuitous hyperbolic adjectives ("unprecedented", "revolutionary", "groundbreaking") and all-caps emphasis.',
        rationale: 'Kobak et al. (2025) demonstrate the methodological framework: excess vocabulary tracking detects LLM-fingerprint words statistically, with several undue-emphasis markers among their 379-word list. The Wikipedia Signs of AI Writing guide (2025) explicitly documents "groundbreaking", "renowned", "breathtaking", and equivalent "undue significance" words based on extensive editorial experience.',
        caveats: 'These words appear legitimately in human marketing copy and journalism. The signal is stronger in contexts (academic, technical) where such language is stylistically incongruous.',
        citations: [CITATIONS.kobak2025, CITATIONS.wikipedia_ai],
    },
    {
        name: 'Promotional Language',
        confidence: 'MEDIUM',
        description: 'Detects marketing-register phrases misplaced in non-marketing contexts: "industry-leading", "world-class", "state-of-the-art", "best-in-class", "next-generation".',
        rationale: 'LLMs were trained on vast quantities of commercial web copy. Kobak et al. (2025) empirically demonstrate that LLM vocabulary is biased toward the dominant registers of training data; promotional language appearing in non-marketing contexts (academic writing, journalism, policy) is therefore a contextual incongruity signal.',
        caveats: 'This detector produces the highest false-positive rate when applied to actual marketing or product documentation, where these phrases are entirely appropriate.',
        citations: [CITATIONS.kobak2025, CITATIONS.wikipedia_ai],
    },
    {
        name: 'Elegant Variation',
        confidence: 'MEDIUM',
        description: 'Detects synonym cycling within five clusters (company/organisation/firm; use/utilise/employ; show/demonstrate/illustrate; problem/issue/challenge; help/assist/support) — flagged when 3+ distinct members of a cluster appear in the text.',
        rationale: 'Reinhart et al. (2025) document systematic vocabulary substitution as a distinguishing LLM characteristic. Reviriego et al. (2024) directly compare vocabulary distribution between ChatGPT and humans, finding that GPT-4 achieves apparent lexical variety through synonym rotation rather than natural word choice. Wikipedia\'s Signs of AI Writing (2025) explicitly names "Elegant variation: Repeated synonym substitution to avoid word repetition" as a documented AI writing pattern.',
        caveats: 'Synonym cycling is also a stylistic device taught in human writing instruction. The detector requires 3+ cluster members, and a short text may trigger it by chance.',
        citations: [CITATIONS.reinhart2025, CITATIONS.reviriego2024, CITATIONS.wikipedia_ai],
    },
];

const LINGUISTIC_ANALYSERS: Detector[] = [
    {
        name: 'Lexical Diversity (Type-Token Ratio)',
        confidence: 'HIGH',
        description: 'Measures the ratio of unique words to total words. AI text often exhibits abnormally high TTR (synonym cycling to avoid repetition) or abnormally low TTR (limited vocabulary range).',
        rationale: 'Muñoz-Ortiz et al. (2024) quantitatively measured lexical diversity across six LLMs vs human-written news, finding human text exhibits consistently richer vocabulary (STTR: 0.491 vs 0.424–0.466 for models). Reviriego et al. (2024) directly compare TTR between ChatGPT versions and humans, finding GPT-3.5 scores lower than humans. Terčon & Dobrovoljc (2025) synthesise the literature, confirming "reduced lexical diversity" as among the most consistently replicated AI text features.',
        caveats: 'GPT-4+ narrows the gap with humans significantly. TTR is also sensitive to text length — longer texts naturally produce lower TTR even for human authors.',
        citations: [CITATIONS.munoz2024, CITATIONS.reviriego2024, CITATIONS.tercon2025],
    },
    {
        name: 'Sentence Length Variation',
        confidence: 'HIGH',
        description: 'Measures coefficient of variation (CV) in sentence word counts. AI text tends toward unnaturally uniform sentence lengths; low CV (below 0.35) is flagged as an alert.',
        rationale: 'Muñoz-Ortiz et al. (2024) directly find that "human-generated sentences display a wider range of lengths and greater diversity" while LLM outputs cluster in the 10–30 token range. Terčon & Dobrovoljc (2025) synthesise multiple studies: "sentence lengths in AI-generated text tend to vary much less compared to human-written text." GPTZero operationalised this as "burstiness" and applied it in production detection.',
        caveats: 'The specific CV thresholds (0.35 alert, 0.45 normal) are calibrated on general prose. Academic and technical writing may naturally show lower variation than creative or conversational text.',
        citations: [CITATIONS.munoz2024, CITATIONS.tercon2025],
    },
    {
        name: 'Passive Voice Frequency',
        confidence: 'HIGH',
        description: 'Measures the proportion of sentences containing passive constructions. Contrary to common assumption, a LOW passive rate is the AI signal — modern LLMs use passive voice at roughly half the human rate.',
        rationale: 'Reinhart et al. (2025) apply Biber\'s feature framework and find that instruction-tuned LLMs use agentless passive voice at approximately half the rate of human writers — GPT-4o diverges most strongly. This directly contradicts the widespread assumption that AI overuses passive voice. Mekala et al. (2024) confirm passive voice rate is a usable feature achieving high accuracy in a multi-feature classifier, regardless of direction.',
        caveats: 'This finding is specific to instruction-tuned frontier models (GPT-4o, Claude, Gemini). Older or less fine-tuned models may behave differently. Domain matters significantly — formal academic writing uses passive voice at higher rates than journalism.',
        citations: [CITATIONS.reinhart2025, CITATIONS.mekala2024],
    },
    {
        name: 'Transition Word Density',
        confidence: 'MEDIUM',
        description: 'Measures the proportion of sentences beginning with formal discourse markers (furthermore, moreover, consequently, nevertheless, accordingly, etc.). AI text overuses these at the cost of natural flow.',
        rationale: 'Wu et al. (2025) identify heavy reliance on cohesive devices and formal discourse markers as a documented LLM linguistic fingerprint. Muñoz-Ortiz et al. (2024) find LLMs produce considerably more subordinate clause constructions — the syntactic structure typically introduced by formal connectives — than human writers. Terčon & Dobrovoljc (2025) confirm "more formal style" and distinctive discourse marker patterns across the literature.',
        caveats: 'Threshold calibration depends heavily on genre. Academic writing legitimately uses high transition-word density. This signal is most reliable for general-purpose prose.',
        citations: [CITATIONS.wu2025, CITATIONS.munoz2024, CITATIONS.tercon2025],
    },
    {
        name: 'Flesch-Kincaid Grade Level',
        confidence: 'HIGH',
        description: 'Measures reading complexity via sentence length and syllable density. Unprompted AI output tends to produce artificially elevated grade levels (Grade >14).',
        rationale: 'Fredrick & Craven (2025) find ChatGPT produces greater lexical complexity than human writers, resulting in paradoxically lower communicative appropriateness — elevated FK grade without meaningful depth. Multiple medical communication studies independently document unprompted ChatGPT responses requiring 14–16 years of formal education, far above recommended health communication levels. Mekala et al. (2024) include Flesch Reading Ease as an explicit detection feature in a near-perfect accuracy classifier.',
        caveats: 'This signal is prompt-dependent: AI will produce appropriately simple text if asked to. It applies most reliably to unguided or lightly prompted AI text.',
        citations: [CITATIONS.fredrick2025, CITATIONS.mekala2024],
    },
    {
        name: 'Punctuation Patterns',
        confidence: 'MEDIUM',
        description: 'Measures per-sentence rates of semicolons and em-dashes. High rates of either are flagged as potential AI signals.',
        rationale: 'Reinhart et al. (2025) capture punctuation-related structural differences between LLMs and humans via the Biber feature framework. The em-dash pattern is well-documented in practitioner analysis: multiple AI systems (particularly OpenAI models) have been observed to use em-dashes at dramatically higher rates than human writers, in what became widely noted as a recognisable stylistic signature.',
        caveats: 'Em-dash overuse is notably model-specific — observed strongly in ChatGPT/DeepSeek, much less so in Claude and Gemini. This makes it an unreliable universal signal. Rolling Stone (2024) noted ChatGPT itself acknowledges this pattern while cautioning it is not definitive.',
        citations: [CITATIONS.reinhart2025, CITATIONS.wikipedia_ai],
    },
    {
        name: 'Rare Word Usage',
        confidence: 'HIGH',
        description: 'Measures the proportion of words outside the 5,000 most common English words (Norvig corpus). Both extremes are flagged: very low rare-word rate (AI oversimplification) and very high rate (AI vocabulary inflation).',
        rationale: 'Kobak et al. (2025) demonstrate LLM vocabulary is detectable via statistically anomalous word-frequency distributions — specific rare-to-moderately-rare words appear at 100× their baseline frequency in LLM output. Muñoz-Ortiz et al. (2024) confirm human text consistently uses greater vocabulary variety (STTR 0.491 vs 0.424–0.466). Reviriego et al. (2024) show GPT-3.5 avoids rare words (limiting vocabulary), while GPT-4 achieves range through synonym rotation. The word list is sourced from Norvig\'s Google Web Corpus frequency data.',
        caveats: 'The Norvig common-words list is web-corpus-based and may underrepresent domain vocabulary. Technical or academic texts will legitimately score higher rare-word rates.',
        citations: [CITATIONS.kobak2025, CITATIONS.munoz2024, CITATIONS.reviriego2024, CITATIONS.norvig],
    },
    {
        name: "Zipf's Law",
        confidence: 'HIGH',
        description: 'Measures how closely word frequency distribution follows the natural Zipfian curve (frequency ∝ 1/rank). Low R² against the ideal curve indicates an unusual distribution.',
        rationale: "Mikhaylovskiy (2025) demonstrates directly that LLM-generated text only complies with Zipf's and Heaps' laws within narrow, model-specific temperature ranges, with measurable phase transitions outside these ranges — AI text deviates from natural-language Zipfian distributions in ways that correlate with generation settings. Zipf's Law is one of the most robust and well-replicated quantitative properties of natural human language.",
        caveats: "Deviation is generation-parameter-dependent (sampling temperature) rather than absolute. Very short texts (<100 words) cannot be reliably assessed. A comparative Glottometrics study (2025) notes no single universal criterion reliably distinguishes human from AI text using word frequency distributions alone.",
        citations: [CITATIONS.mikhaylovskiy2025],
    },
];

// ─── Sub-components ───────────────────────────────────────────────────────────

function ConfidenceBadge({ level }: { level: 'HIGH' | 'MEDIUM' | 'LOW' }) {
    const styles = {
        HIGH:   'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 border border-green-300 dark:border-green-700',
        MEDIUM: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200 border border-yellow-300 dark:border-yellow-700',
        LOW:    'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200 border border-red-300 dark:border-red-700',
    };
    return (
        <span className={`inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold ${styles[level]}`}>
            {level} CONFIDENCE
        </span>
    );
}

function CitationTag({ c }: { c: Citation }) {
    return (
        <div className="text-xs text-gray-500 dark:text-gray-400 mt-1">
            <a
                href={c.url}
                target="_blank"
                rel="noopener noreferrer"
                className="hover:text-blue-600 dark:hover:text-blue-400 underline underline-offset-2"
            >
                {c.authors} ({c.year}). <em>{c.title}</em>. {c.venue}.
            </a>
            {c.note && <span className="ml-1 italic opacity-75">({c.note})</span>}
        </div>
    );
}

function DetectorCard({ d }: { d: Detector }) {
    return (
        <div className="border border-gray-200 dark:border-gray-700 rounded-lg p-5 bg-white dark:bg-gray-900">
            <div className="flex flex-wrap items-center gap-2 mb-3">
                <h3 className="text-base font-semibold text-gray-900 dark:text-gray-100">{d.name}</h3>
                <ConfidenceBadge level={d.confidence} />
            </div>
            <p className="text-sm text-gray-700 dark:text-gray-300 mb-3">{d.description}</p>
            <div className="mb-3">
                <p className="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-1">Scientific basis</p>
                <p className="text-sm text-gray-600 dark:text-gray-400">{d.rationale}</p>
            </div>
            {d.caveats && (
                <div className="mb-3 bg-amber-50 dark:bg-amber-950 border border-amber-200 dark:border-amber-800 rounded p-3">
                    <p className="text-xs font-medium uppercase tracking-wide text-amber-700 dark:text-amber-400 mb-1">Caveats</p>
                    <p className="text-xs text-amber-800 dark:text-amber-300">{d.caveats}</p>
                </div>
            )}
            <div>
                <p className="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-1">Citations</p>
                {d.citations.map((c, i) => <CitationTag key={i} c={c} />)}
            </div>
        </div>
    );
}

// ─── Page ─────────────────────────────────────────────────────────────────────

export default function About() {
    return (
        <AppLayout>
            <Head title="About & Methodology" />
            <div className="max-w-4xl mx-auto px-4 py-10">

                {/* Header */}
                <div className="mb-10">
                    <h1 className="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-3">
                        About & Methodology
                    </h1>
                    <p className="text-lg text-gray-600 dark:text-gray-400">
                        How <em>I Can't Do That, Dave</em> works, what the science says, and where the evidence is thin.
                    </p>
                </div>

                {/* Methodology overview */}
                <section className="mb-10 prose prose-gray dark:prose-invert max-w-none">
                    <div className="bg-blue-50 dark:bg-blue-950 border border-blue-200 dark:border-blue-800 rounded-lg p-6 mb-6">
                        <h2 className="text-lg font-semibold text-blue-900 dark:text-blue-100 mt-0 mb-2">What this tool does</h2>
                        <p className="text-blue-800 dark:text-blue-200 text-sm mb-0">
                            This is a <strong>heuristic ensemble detector</strong>. It applies 11 pattern-matching detectors and
                            8 statistical linguistic analysers, each grounded in published research, and combines their signals
                            into a 0–100 score. No single heuristic is definitive — the score reflects convergent evidence
                            from multiple independent signals.
                        </p>
                    </div>
                    <div className="bg-red-50 dark:bg-red-950 border border-red-200 dark:border-red-800 rounded-lg p-6">
                        <h2 className="text-lg font-semibold text-red-900 dark:text-red-100 mt-0 mb-2">Limitations you must understand</h2>
                        <ul className="text-red-800 dark:text-red-200 text-sm space-y-1 mb-0 list-disc list-inside">
                            <li>All AI detection tools produce false positives. Non-native English writers are particularly at risk (Stanford HAI, 2023).</li>
                            <li>This tool should <strong>never</strong> be used as sole evidence of AI authorship in academic misconduct proceedings.</li>
                            <li>Detection heuristics are engaged in an arms race with model development. Signals that were reliable in 2023 may have weakened by 2026.</li>
                            <li>Some detectors (notably False Range) have LOW confidence ratings — they are included as weak contributing signals only.</li>
                            <li>The passive voice detector reflects empirical evidence that modern LLMs use <em>less</em> passive voice than humans — the opposite of the common assumption.</li>
                        </ul>
                    </div>
                </section>

                {/* Pattern Detectors */}
                <section className="mb-12">
                    <h2 className="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-2">Pattern Detectors</h2>
                    <p className="text-sm text-gray-500 dark:text-gray-400 mb-6">
                        Each detector scans for specific phrases or constructions and contributes a capped point score.
                    </p>
                    <div className="space-y-4">
                        {PATTERN_DETECTORS.map(d => <DetectorCard key={d.name} d={d} />)}
                    </div>
                </section>

                {/* Linguistic Analysers */}
                <section className="mb-12">
                    <h2 className="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-2">Linguistic Analysers</h2>
                    <p className="text-sm text-gray-500 dark:text-gray-400 mb-6">
                        Statistical measures of the text as a whole. Each contributes fixed bonus points based on alert/warning status.
                    </p>
                    <div className="space-y-4">
                        {LINGUISTIC_ANALYSERS.map(d => <DetectorCard key={d.name} d={d} />)}
                    </div>
                </section>

                {/* Word list provenance */}
                <section className="mb-12">
                    <h2 className="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-4">Common Words List</h2>
                    <div className="border border-gray-200 dark:border-gray-700 rounded-lg p-5 bg-white dark:bg-gray-900 text-sm text-gray-600 dark:text-gray-400">
                        <p className="mb-2">
                            The rare word analyser compares tokens against a list of the 5,000 most common English words.
                            This list is derived from <strong>Peter Norvig's word frequency data</strong>, extracted from the
                            Google Web Corpus (~1 trillion words of English text). The top 5,000 entries by raw frequency
                            were taken from <code>norvig.com/ngrams/count_1w.txt</code>.
                        </p>
                        <CitationTag c={CITATIONS.norvig} />
                    </div>
                </section>

                {/* Full bibliography */}
                <section className="mb-10">
                    <h2 className="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-4">Full Bibliography</h2>
                    <div className="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg p-6 space-y-3">
                        {Object.values(CITATIONS).map((c, i) => (
                            <div key={i} className="text-sm text-gray-600 dark:text-gray-400 pb-3 border-b border-gray-100 dark:border-gray-800 last:border-0 last:pb-0">
                                <a
                                    href={c.url}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="hover:text-blue-600 dark:hover:text-blue-400 underline underline-offset-2"
                                >
                                    {c.authors} ({c.year}). <em>{c.title}</em>. {c.venue}.
                                </a>
                                {c.note && <span className="ml-1 text-gray-400 italic text-xs">({c.note})</span>}
                            </div>
                        ))}
                    </div>
                </section>

                <div className="text-center">
                    <Link
                        href={route('analyser.index')}
                        className="inline-flex items-center gap-2 bg-gray-900 dark:bg-gray-100 text-white dark:text-gray-900 px-6 py-3 rounded-lg font-medium hover:bg-gray-700 dark:hover:bg-gray-300 transition-colors"
                    >
                        ← Back to analyser
                    </Link>
                </div>

            </div>
        </AppLayout>
    );
}
