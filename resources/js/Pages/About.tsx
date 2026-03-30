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
    desaire2023: {
        authors: 'Desaire, H., Chua, A.E., Kim, M.G., & Hua, D.',
        year: '2023',
        title: 'Distinguishing academic science writing from humans or ChatGPT with over 99% accuracy',
        venue: 'Cell Reports Physical Science, 4(6), 101426',
        url: 'https://doi.org/10.1016/j.crphys.2023.101426',
    },
    herbold2023: {
        authors: 'Herbold, S. et al.',
        year: '2023',
        title: 'A large-scale comparison of human-written versus ChatGPT-generated essays',
        venue: 'Nature Scientific Reports',
        url: 'https://doi.org/10.1038/s41598-023-45644-9',
    },
    shaib2024: {
        authors: 'Shaib, C. et al.',
        year: '2024',
        title: 'Detection and Measurement of Syntactic Templates in Generated Text',
        venue: 'EMNLP 2024',
        url: 'https://aclanthology.org/2024.emnlp-main.136/',
    },
    gptzero_vocab: {
        authors: 'GPTZero',
        year: '2024',
        title: 'AI Vocabulary — frequency analysis of 3.3M texts',
        venue: 'GPTZero Research',
        url: 'https://gptzero.me/research',
    },
    maxplanck2024: {
        authors: 'Max Planck Institute',
        year: '2024',
        title: "Empirical evidence of LLMs' influence on human communication",
        venue: 'arXiv:2409.01754',
        url: 'https://arxiv.org/abs/2409.01754',
    },
    arxiv_funcwords: {
        authors: 'Various',
        year: '2024',
        title: 'Function word density as a discriminator for AI-generated text',
        venue: 'arXiv:2407.03646',
        url: 'https://arxiv.org/abs/2407.03646',
    },
    stryng: {
        authors: 'stryng.io',
        year: '2025',
        title: 'AI writing detection patterns',
        venue: 'stryng.io',
        url: 'https://stryng.io',
        note: 'Practitioner source',
    },
};

// ─── Detector definitions ────────────────────────────────────────────────────

const PATTERN_DETECTORS: Detector[] = [
    {
        name: 'Rule of Three',
        confidence: 'HIGH',
        description: 'Detects comma-separated triadic lists: "X, Y, and Z" constructions used at abnormally high frequency. 41% of human texts vs 70% of AI texts trigger this detector (+29pp gap).',
        rationale: 'Reinhart et al. (2025) applied Biber\'s 66-feature linguistic tagset to LLM output and found systematic overuse of parallel constructions and list structures vs human text. Gorrie (2024), in a linguistic PhD-level analysis, specifically identifies the tricolon (rule of three) as among the most mechanically overused rhetorical devices in LLM prose — produced reflexively because training data rewarded tidy three-part enumerations.',
        caveats: 'No study has corpus-quantified this specific construction in isolation. Evidence is empirical (Reinhart) plus expert practitioner observation (Gorrie). Humans use triadic lists too; frequency is the signal, not presence.',
        citations: [CITATIONS.reinhart2025, CITATIONS.gorrie2024],
    },
    {
        name: 'Undue Emphasis',
        confidence: 'MEDIUM',
        description: 'Detects gratuitous hyperbolic adjectives ("unprecedented", "revolutionary", "groundbreaking") and all-caps emphasis. 3% H vs 18% AI (+15pp gap). An acronym allowlist prevents false positives on legitimate uppercase terms like NASA, GDP, etc.',
        rationale: 'Kobak et al. (2025) demonstrate the methodological framework: excess vocabulary tracking detects LLM-fingerprint words statistically, with several undue-emphasis markers among their 379-word list. The Wikipedia Signs of AI Writing guide (2025) explicitly documents "groundbreaking", "renowned", "breathtaking", and equivalent "undue significance" words based on extensive editorial experience.',
        caveats: 'These words appear legitimately in human marketing copy and journalism. The signal is stronger in contexts (academic, technical) where such language is stylistically incongruous.',
        citations: [CITATIONS.kobak2025, CITATIONS.wikipedia_ai],
    },
    {
        name: 'Trailing Participle',
        confidence: 'MEDIUM',
        description: 'Detects trailing participial clauses: ", highlighting...", ", showcasing...", ", underscoring..." appended to sentences. 6% H vs 15% AI (+9pp gap).',
        rationale: 'Wikipedia\'s Signs of AI Writing guide documents this as a recognisable AI pattern. LLMs frequently append participial clauses to add apparent depth without substantive content. The stryng.io practitioner analysis independently identifies this construction as a common AI writing tell.',
        caveats: 'Trailing participles are valid English grammar. The signal is in frequency and mechanical deployment, not mere presence.',
        citations: [CITATIONS.wikipedia_ai, CITATIONS.stryng],
    },
    {
        name: 'Negative Parallelism',
        confidence: 'MEDIUM',
        description: 'Detects formal parallel constructions: "not only ... but also", "neither ... nor". 3% H vs 12% AI (+9pp gap). The "both ... and" pattern was removed after testing showed insufficient discrimination.',
        rationale: 'Reinhart et al. (2025) documents that instruction-tuned LLMs overuse rhetorical parallel and antithetical structures at rates far exceeding human writing. Gorrie (2024) specifically names antithesis ("not X, but Y") as an LLM favourite: "a device that tends to produce memorable lines, which is why it\'s so jarring when it appears in every other paragraph."',
        caveats: 'No paper isolates negative-parallelism constructions as a standalone measurable category. Support is from broader parallel-structure empirical work plus expert observation.',
        citations: [CITATIONS.reinhart2025, CITATIONS.gorrie2024],
    },
    {
        name: 'AI Vocabulary',
        confidence: 'HIGH',
        description: 'Detects terms statistically overrepresented in LLM-generated text. Pruned to highest-signal terms: "pivotal", "holistic", "paradigm", "synergy", and similar. 3% H vs 11% AI (+8pp gap).',
        rationale: 'Kobak et al. (2025) analysed over 15 million PubMed abstracts and identified 379 "excess vocabulary" words whose frequency spiked anomalously after ChatGPT\'s release. Geng & Trotta (2025) tracked the same effect across 1.29 million arXiv abstracts, finding that once markers like "delve" were publicly identified in early 2024, their frequencies dropped — evidence of human editing of LLM output.',
        caveats: 'Individual terms decay in reliability once publicly identified. The word list has been pruned to terms that still show strong discrimination in our 136-fixture test corpus.',
        citations: [CITATIONS.kobak2025, CITATIONS.geng2025],
    },
    {
        name: 'Formulaic Importance',
        confidence: 'MEDIUM',
        description: 'Detects cliched importance phrases: "plays a crucial role", "a testament to", "serves as a". 0% H vs 8% AI (+8pp gap).',
        rationale: 'GPTZero\'s analysis of 3.3 million texts identified these formulaic importance markers as statistically overrepresented in AI-generated content. LLMs default to these constructions when signalling significance, producing a distinctive repetitive pattern absent from human writing in our test corpus.',
        caveats: 'These phrases do appear in human writing, particularly in formal or academic contexts. The 0% human rate in our corpus may not generalise to all domains.',
        citations: [CITATIONS.gptzero_vocab],
    },
    {
        name: 'Grandiose Nouns',
        confidence: 'MEDIUM',
        description: 'Detects metaphorical nouns used as rhetorical inflation: "tapestry", "landscape", "realm of", "beacon of". 0% H vs 7% AI (+7pp gap).',
        rationale: 'Max Planck Institute (2024) documented empirical evidence of LLM influence on human communication, identifying grandiose metaphorical nouns as a distinctive feature of AI-generated prose. These terms serve as rhetorical filler, elevating mundane concepts with unearned gravitas.',
        caveats: 'These words have legitimate uses in their literal senses. The detector targets their metaphorical deployment as rhetorical devices.',
        citations: [CITATIONS.maxplanck2024],
    },
    {
        name: 'Epistemic Markers',
        confidence: 'MEDIUM',
        description: 'Detects hedging phrases characteristic of human uncertainty: "I think", "perhaps", "in my opinion", "I believe". This is a human signal — it contributes negative (human-leaning) points.',
        rationale: 'Herbold et al. (2023) found in a large-scale comparison of human-written vs ChatGPT-generated essays that human writers use significantly more epistemic hedging and first-person uncertainty markers. LLMs tend toward confident, authoritative phrasing rather than tentative personal expression.',
        caveats: 'This is a human-direction signal (reduces the AI score). Its absence does not indicate AI authorship — many human writers, particularly in formal contexts, also avoid hedging language.',
        citations: [CITATIONS.herbold2023],
    },
    {
        name: 'Outline & Conclusion',
        confidence: 'LOW',
        description: 'Detects formulaic closing openers ("In conclusion,", "To summarise,", "Ultimately,") and the "Despite [X], [subject] still..." contrast-then-resolve pattern.',
        rationale: 'Wu et al. (2025), the field\'s most comprehensive peer-reviewed detection survey, identifies formulaic syntactic constructions and predictable discourse structuring as documented LLM linguistic fingerprints.',
        caveats: 'Deprecated (score = 0). Showed insufficient discrimination in empirical testing against our fixture corpus. Retained for transparency.',
        citations: [CITATIONS.wu2025, CITATIONS.wikipedia_ai],
    },
    {
        name: 'False Range',
        confidence: 'LOW',
        description: 'Detects abstract "from X to Y" constructions where neither X nor Y is a number, unit, or scalar quantity — e.g. "from innovation to implementation".',
        rationale: 'This is a practitioner-observed pattern documented in the Wikipedia Signs of AI Writing guide.',
        caveats: 'Deprecated (score = 0). Insufficient discrimination in empirical testing. Retained for transparency.',
        citations: [CITATIONS.wikipedia_ai, CITATIONS.reinhart2025],
    },
    {
        name: 'Vague Attribution',
        confidence: 'LOW',
        description: 'Detects claims attributed to unnamed authorities: "studies show", "experts agree", "research suggests".',
        rationale: 'SAGE Publishing\'s June 2025 guidance lists this as a red flag for AI-generated submissions.',
        caveats: 'Deprecated (score = 0). Human writers in journalism and popular science also use vague attribution regularly, producing too many false positives. Retained for transparency.',
        citations: [CITATIONS.sage2025, CITATIONS.peters2025],
    },
    {
        name: 'Superficial Analysis',
        confidence: 'LOW',
        description: 'Detects hedging filler phrases: "it is worth noting", "one could argue", "needless to say".',
        rationale: 'SAGE (2025) describes "Flawless but Empty Prose" as a primary AI tell.',
        caveats: 'Deprecated (score = 0). Insufficient discrimination in empirical testing. Retained for transparency.',
        citations: [CITATIONS.sage2025, CITATIONS.pudasaini2026],
    },
    {
        name: 'Overgeneralisation',
        confidence: 'LOW',
        description: 'Detects sweeping universal claims: "everyone knows", "universal consensus", "it is well established".',
        rationale: 'Peters & Chin-Yee (2025) found LLM summaries were nearly five times more likely to contain broad generalisations than human expert summaries.',
        caveats: 'Deprecated (score = 0). Insufficient discrimination in empirical testing. Retained for transparency.',
        citations: [CITATIONS.peters2025],
    },
    {
        name: 'Promotional Language',
        confidence: 'LOW',
        description: 'Detects marketing-register phrases misplaced in non-marketing contexts: "industry-leading", "world-class", "state-of-the-art".',
        rationale: 'Kobak et al. (2025) empirically demonstrate that LLM vocabulary is biased toward the dominant registers of training data.',
        caveats: 'Deprecated (score = 0). Insufficient discrimination in empirical testing. Retained for transparency.',
        citations: [CITATIONS.kobak2025, CITATIONS.wikipedia_ai],
    },
    {
        name: 'Elegant Variation',
        confidence: 'LOW',
        description: 'Detects synonym cycling within clusters — flagged when 3+ distinct members of a cluster appear in the text.',
        rationale: 'Reinhart et al. (2025) document systematic vocabulary substitution as a distinguishing LLM characteristic.',
        caveats: 'Deprecated (score = 0). Insufficient discrimination in empirical testing. Retained for transparency.',
        citations: [CITATIONS.reinhart2025, CITATIONS.reviriego2024, CITATIONS.wikipedia_ai],
    },
];

const LINGUISTIC_ANALYSERS: Detector[] = [
    {
        name: 'Question Frequency',
        confidence: 'HIGH',
        description: 'Measures the percentage of sentences that are questions. Alert when < 3% are questions (+28pp gap). This is the strongest single linguistic signal in our corpus.',
        rationale: 'Desaire et al. (2023) found that question usage was one of the most discriminating features between human and AI academic writing, achieving over 99% classification accuracy in combination with other features. AI text defaults to declarative statements and rarely poses questions, while human writers naturally use rhetorical and genuine questions.',
        caveats: 'The signal strength varies by genre — academic and journalistic writing may have naturally low question rates. Most effective on conversational or editorial prose.',
        citations: [CITATIONS.desaire2023],
    },
    {
        name: 'Sentence Opener Diversity',
        confidence: 'HIGH',
        description: 'Measures the percentage of sentences with unique opening words. Alert when < 60% unique openers (+25pp gap).',
        rationale: 'Shaib et al. (2024) at EMNLP documented the detection and measurement of syntactic templates in generated text, showing that AI writing exhibits repetitive syntactic patterns including sentence-initial structures. AI text tends to recycle a small set of sentence openers ("The", "This", "However"), while human writing shows greater variety.',
        caveats: 'Short texts naturally have higher diversity due to fewer opportunities for repetition. The threshold is calibrated on texts of 200+ words.',
        citations: [CITATIONS.shaib2024],
    },
    {
        name: 'Sentence Length CV',
        confidence: 'HIGH',
        description: 'Measures coefficient of variation (CV) in sentence word counts. AI text tends toward unnaturally uniform sentence lengths; CV below 0.35 is flagged as an alert (+24pp gap).',
        rationale: 'Muñoz-Ortiz et al. (2024) directly find that "human-generated sentences display a wider range of lengths and greater diversity" while LLM outputs cluster in the 10–30 token range. Terčon & Dobrovoljc (2025) synthesise multiple studies: "sentence lengths in AI-generated text tend to vary much less compared to human-written text."',
        caveats: 'The specific CV thresholds (0.35 alert, 0.45 normal) are calibrated on general prose. Academic and technical writing may naturally show lower variation than creative or conversational text.',
        citations: [CITATIONS.munoz2024, CITATIONS.tercon2025],
    },
    {
        name: 'Paragraph Length Variance',
        confidence: 'HIGH',
        description: 'Measures the coefficient of variation in paragraph lengths. Alert when CV < 0.12 (+21pp gap, 0% human false positive rate).',
        rationale: 'Desaire et al. (2023) identified paragraph structure uniformity as a discriminating feature. AI-generated text produces remarkably uniform paragraph lengths, while human writers vary paragraph size naturally based on content and rhetorical needs.',
        caveats: 'Requires texts with 3+ paragraphs to be meaningful. Single-paragraph or very short texts cannot be assessed.',
        citations: [CITATIONS.desaire2023],
    },
    {
        name: 'Function Word Density',
        confidence: 'MEDIUM',
        description: 'Measures the proportion of function words (articles, prepositions, conjunctions, auxiliaries) relative to total words. Alert when < 40% (+21pp gap).',
        rationale: 'Function words serve as structural scaffolding in natural language. AI text tends to be content-word heavy, producing informationally dense prose with fewer of the grammatical "glue" words that characterise natural human expression.',
        caveats: 'Domain-dependent. Technical writing naturally has different function word rates than conversational prose.',
        citations: [CITATIONS.arxiv_funcwords],
    },
    {
        name: 'Sentence Extremes Ratio',
        confidence: 'MEDIUM',
        description: 'Measures the proportion of sentences that are very short (< 8 words) or very long (> 35 words). Alert when < 15% extreme sentences (+14pp gap).',
        rationale: 'Desaire et al. (2023) found sentence length distribution to be a powerful discriminator. Human writers produce occasional very short punchy sentences and long complex ones; AI text clusters in a narrow mid-range, avoiding both extremes.',
        caveats: 'Genre-dependent. Some formal writing styles (legal, regulatory) naturally avoid sentence extremes.',
        citations: [CITATIONS.desaire2023],
    },
    {
        name: 'Transition Word Density',
        confidence: 'MEDIUM',
        description: 'Measures the proportion of sentences beginning with formal discourse markers (furthermore, moreover, consequently, nevertheless, accordingly, etc.). Fires on 0% of human texts, AI only.',
        rationale: 'Wu et al. (2025) identify heavy reliance on cohesive devices and formal discourse markers as a documented LLM linguistic fingerprint. Muñoz-Ortiz et al. (2024) find LLMs produce considerably more subordinate clause constructions than human writers.',
        caveats: 'Threshold calibration depends heavily on genre. Academic writing legitimately uses high transition-word density. This signal is most reliable for general-purpose prose.',
        citations: [CITATIONS.wu2025, CITATIONS.munoz2024, CITATIONS.tercon2025],
    },
    {
        name: 'Punctuation Patterns',
        confidence: 'MEDIUM',
        description: 'Measures per-sentence rates of semicolons and em-dashes. High rates of either are flagged as potential AI signals.',
        rationale: 'Reinhart et al. (2025) capture punctuation-related structural differences between LLMs and humans via the Biber feature framework. The em-dash pattern is well-documented: multiple AI systems (particularly OpenAI models) use em-dashes at dramatically higher rates than human writers.',
        caveats: 'Em-dash overuse is notably model-specific — observed strongly in ChatGPT/DeepSeek, much less so in Claude and Gemini. This makes it an unreliable universal signal.',
        citations: [CITATIONS.reinhart2025, CITATIONS.wikipedia_ai],
    },
    {
        name: 'Flesch-Kincaid Grade Level',
        confidence: 'MEDIUM',
        description: 'Measures reading complexity via sentence length and syllable density. A moderate differentiator — unprompted AI output tends to produce artificially elevated grade levels.',
        rationale: 'Fredrick & Craven (2025) find ChatGPT produces greater lexical complexity than human writers, resulting in paradoxically lower communicative appropriateness — elevated FK grade without meaningful depth.',
        caveats: 'This signal is prompt-dependent: AI will produce appropriately simple text if asked to. It applies most reliably to unguided or lightly prompted AI text.',
        citations: [CITATIONS.fredrick2025, CITATIONS.mekala2024],
    },
    {
        name: 'Passive Voice Rate',
        confidence: 'LOW',
        description: 'Measures the proportion of sentences containing passive constructions. 21% H vs 20% AI — no meaningful difference.',
        rationale: 'Reinhart et al. (2025) apply Biber\'s feature framework and find that instruction-tuned LLMs use agentless passive voice at approximately half the rate of human writers. However, our empirical testing found no meaningful gap.',
        caveats: 'Deprecated (score = 0). 21% H vs 20% AI showed no discrimination in our fixture corpus. Retained for transparency.',
        citations: [CITATIONS.reinhart2025, CITATIONS.mekala2024],
    },
    {
        name: 'Lexical Diversity (TTR)',
        confidence: 'LOW',
        description: 'Measures the ratio of unique words to total words. 74% H vs 73% AI — no meaningful difference.',
        rationale: 'Muñoz-Ortiz et al. (2024) found human text exhibits richer vocabulary in controlled studies, but in practice the gap was negligible in our fixture corpus.',
        caveats: 'Deprecated (score = 0). 74% vs 73% showed no discrimination. Retained for transparency.',
        citations: [CITATIONS.munoz2024, CITATIONS.reviriego2024, CITATIONS.tercon2025],
    },
    {
        name: 'Rare Word Usage',
        confidence: 'LOW',
        description: 'Measures the proportion of words outside the 5,000 most common English words. 100% pass rate for both human and AI.',
        rationale: 'Kobak et al. (2025) demonstrate LLM vocabulary is detectable via anomalous word-frequency distributions, but the specific rare-word thresholds showed no discrimination in practice.',
        caveats: 'Deprecated (score = 0). 100% vs 100% — no discrimination. Retained for transparency.',
        citations: [CITATIONS.kobak2025, CITATIONS.norvig],
    },
    {
        name: "Zipf's Law",
        confidence: 'LOW',
        description: "Measures how closely word frequency distribution follows the natural Zipfian curve. 100% pass rate for both human and AI.",
        rationale: "Mikhaylovskiy (2025) demonstrates LLM text deviates from Zipfian distributions, but the effect was not measurable in our fixture corpus at the text lengths tested.",
        caveats: 'Deprecated (score = 0). 100% vs 100% — no discrimination. Retained for transparency.',
        citations: [CITATIONS.mikhaylovskiy2025],
    },
    {
        name: 'Shannon Entropy',
        confidence: 'LOW',
        description: 'Measures the information entropy of the text\'s word distribution. 100% pass rate for both human and AI.',
        rationale: 'Information-theoretic measures have been proposed as AI text discriminators, but the specific entropy thresholds showed no discrimination in our fixture corpus.',
        caveats: 'Deprecated (score = 0). 100% vs 100% — no discrimination. Retained for transparency.',
        citations: [CITATIONS.munoz2024],
    },
    {
        name: 'N-gram Repetition',
        confidence: 'LOW',
        description: 'Measures the frequency of repeated n-grams in text. 100% pass rate for both human and AI.',
        rationale: 'N-gram repetition has been proposed as an AI text feature, but showed no discrimination at the text lengths in our fixture corpus.',
        caveats: 'Deprecated (score = 0). 100% vs 100% — no discrimination. Retained for transparency.',
        citations: [CITATIONS.tercon2025],
    },
    {
        name: 'Hapax Legomenon Ratio',
        confidence: 'LOW',
        description: 'Measures the proportion of words that appear exactly once. 100% pass rate for both human and AI.',
        rationale: 'Hapax legomena (words occurring only once) are a classical stylometric feature, but showed no discrimination between human and AI text in our corpus.',
        caveats: 'Deprecated (score = 0). 100% vs 100% — no discrimination. Retained for transparency.',
        citations: [CITATIONS.reviriego2024],
    },
    {
        name: 'Adjacent Sentence Delta',
        confidence: 'LOW',
        description: 'Measures the average word-count difference between adjacent sentences. 65% H vs 59% AI — a slight human signal but insufficient for scoring.',
        rationale: 'This measures "burstiness" at a more granular level than overall sentence length CV, but the gap was too small and in the wrong direction for AI detection.',
        caveats: 'Deprecated (score = 0). 65% H vs 59% AI — slight human signal, insufficient discrimination. Retained for transparency.',
        citations: [CITATIONS.munoz2024],
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
                            This is a <strong>heuristic ensemble detector</strong>. It applies 8 active pattern-matching detectors and
                            9 active statistical linguistic analysers, each grounded in published research, and combines their signals
                            into a 0&ndash;100 score. No single heuristic is definitive — the score reflects convergent evidence
                            from multiple independent signals. Deprecated detectors (score = 0) are retained for transparency.
                        </p>
                    </div>
                    <div className="bg-red-50 dark:bg-red-950 border border-red-200 dark:border-red-800 rounded-lg p-6">
                        <h2 className="text-lg font-semibold text-red-900 dark:text-red-100 mt-0 mb-2">Limitations you must understand</h2>
                        <ul className="text-red-800 dark:text-red-200 text-sm space-y-1 mb-0 list-disc list-inside">
                            <li>All AI detection tools produce false positives. Non-native English writers are particularly at risk (Stanford HAI, 2023).</li>
                            <li>This tool should <strong>never</strong> be used as sole evidence of AI authorship in academic misconduct proceedings.</li>
                            <li>Detection heuristics are engaged in an arms race with model development. Signals that were reliable in 2023 may have weakened by 2026.</li>
                            <li>Many detectors and analysers from the original build have been deprecated after empirical testing showed insufficient discrimination. They are retained at score = 0 for transparency.</li>
                            <li>Research older than 12 months is particularly unreliable for AI detection — LLM writing style evolves rapidly.</li>
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

                {/* Scoring System */}
                <section className="mb-12">
                    <h2 className="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-2">Scoring System</h2>
                    <p className="text-sm text-gray-500 dark:text-gray-400 mb-6">
                        How individual detector and analyser signals combine into the final 0&ndash;100 score.
                    </p>
                    <div className="space-y-4">
                        <div className="border border-gray-200 dark:border-gray-700 rounded-lg p-5 bg-white dark:bg-gray-900">
                            <h3 className="text-base font-semibold text-gray-900 dark:text-gray-100 mb-3">Detector Breadth Bonus</h3>
                            <p className="text-sm text-gray-600 dark:text-gray-400 mb-3">
                                The number of distinct pattern detectors that fire affects the score via a breadth bonus, rewarding convergent evidence from multiple independent signals:
                            </p>
                            <div className="grid grid-cols-2 sm:grid-cols-4 gap-2 text-sm">
                                <div className="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded p-3 text-center">
                                    <div className="font-bold text-green-700 dark:text-green-400">0 detectors</div>
                                    <div className="text-green-600 dark:text-green-500 text-xs mt-1">&minus;15 points</div>
                                </div>
                                <div className="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded p-3 text-center">
                                    <div className="font-bold text-green-700 dark:text-green-400">1 detector</div>
                                    <div className="text-green-600 dark:text-green-500 text-xs mt-1">&minus;10 points</div>
                                </div>
                                <div className="bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded p-3 text-center">
                                    <div className="font-bold text-gray-700 dark:text-gray-300">2&ndash;3 detectors</div>
                                    <div className="text-gray-500 dark:text-gray-400 text-xs mt-1">0 points</div>
                                </div>
                                <div className="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded p-3 text-center">
                                    <div className="font-bold text-red-700 dark:text-red-400">4+ detectors</div>
                                    <div className="text-red-600 dark:text-red-500 text-xs mt-1">+20 points</div>
                                </div>
                            </div>
                        </div>
                        <div className="border border-gray-200 dark:border-gray-700 rounded-lg p-5 bg-white dark:bg-gray-900">
                            <h3 className="text-base font-semibold text-gray-900 dark:text-gray-100 mb-3">Human Signal Scoring</h3>
                            <p className="text-sm text-gray-600 dark:text-gray-400">
                                Some features (like Epistemic Markers) are <strong>human signals</strong> — they contribute negative points, pulling the score <em>toward</em> human. This means the system actively recognises human writing patterns, not just AI patterns. A text rich in hedging, first-person uncertainty, and rhetorical questions will score lower even if some AI pattern detectors fire.
                            </p>
                        </div>
                        <div className="border border-gray-200 dark:border-gray-700 rounded-lg p-5 bg-white dark:bg-gray-900">
                            <h3 className="text-base font-semibold text-gray-900 dark:text-gray-100 mb-3">Score Thresholds</h3>
                            <div className="grid grid-cols-3 gap-3 text-sm mb-3">
                                <div className="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded p-3 text-center">
                                    <div className="font-bold text-green-700 dark:text-green-400">0&ndash;29</div>
                                    <div className="text-green-600 dark:text-green-500 text-xs mt-1">Likely Human-Written</div>
                                </div>
                                <div className="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded p-3 text-center">
                                    <div className="font-bold text-yellow-700 dark:text-yellow-400">30&ndash;44</div>
                                    <div className="text-yellow-600 dark:text-yellow-500 text-xs mt-1">Possibly AI-Generated</div>
                                </div>
                                <div className="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded p-3 text-center">
                                    <div className="font-bold text-red-700 dark:text-red-400">45&ndash;100</div>
                                    <div className="text-red-600 dark:text-red-500 text-xs mt-1">Likely AI-Generated</div>
                                </div>
                            </div>
                        </div>
                        <div className="border border-gray-200 dark:border-gray-700 rounded-lg p-5 bg-white dark:bg-gray-900">
                            <h3 className="text-base font-semibold text-gray-900 dark:text-gray-100 mb-3">Empirical Validation</h3>
                            <p className="text-sm text-gray-600 dark:text-gray-400 mb-2">
                                All detectors and thresholds were calibrated against a corpus of <strong>136 fixture texts</strong> spanning <strong>7 genres</strong> (academic journalism, opinion essays, news reports, technical writing, and AI-generated equivalents from Claude, Gemini, and ChatGPT). Each detector was individually evaluated for its human-vs-AI discrimination gap (percentage point difference in trigger rates).
                            </p>
                            <p className="text-sm text-gray-600 dark:text-gray-400">
                                <strong>Key lesson:</strong> Research papers older than ~12 months proved unreliable for calibration. Features reported as strong discriminators in 2023&ndash;2024 studies frequently showed no discrimination in our 2026 corpus, likely because LLM writing style evolves faster than the publication cycle. The deprecated detectors above are direct evidence of this effect.
                            </p>
                        </div>
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
