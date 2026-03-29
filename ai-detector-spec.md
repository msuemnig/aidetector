# AI Text Detector — Claude Code Spec

## Project Overview

Build a standalone web-based AI text detection tool. Users paste or type text into a form, submit it, and receive a detailed report indicating the likelihood that the text was AI-generated, along with a breakdown of every signal that contributed to the score.

---

## Stack

- **Framework:** Laravel 11 (standalone new project, not integrated into any existing repo)
- **Frontend:** Inertia.js + React (TypeScript)
- **Styling:** Tailwind CSS
- **State:** React `useState` / `useEffect` only — no external state library needed
- **No database required** — all analysis is stateless and performed per-request

Scaffold the project with:

```bash
composer create-project laravel/laravel ai-detector
cd ai-detector
composer require inertiajs/inertia-laravel
npm install @inertiajs/react react react-dom
```

Configure Inertia server-side adapter and client-side adapter per the official Inertia docs. Use Vite as the asset bundler (Laravel default).

---

## Application Structure

### Routes

```
GET  /          → AnalyserController@index   (renders the input page)
POST /analyse   → AnalyserController@analyse (runs analysis, returns results via Inertia)
```

### Controllers

**`app/Http/Controllers/AnalyserController.php`**
- `index()` — returns the `Analyser/Index` Inertia page with no props
- `analyse(Request $request)` — validates that `text` is present and non-empty, instantiates the analysis pipeline, returns the `Analyser/Results` Inertia page with the full result payload as props

### Service Layer

All detection logic lives in `app/Services/AiDetector/`. Each detector is its own class implementing a shared interface:

```php
interface DetectorInterface
{
    public function analyse(string $text): DetectorResult;
}
```

`DetectorResult` is a plain PHP object / DTO with:
- `string $category` — display name
- `int $score` — points contributed (0 to cap)
- `int $cap` — maximum points this detector can contribute
- `int $occurrences` — how many times the pattern was found
- `array $matches` — the actual matched strings or phrases
- `string $explanation` — human-readable summary of what was found

The main `AnalysisService` accepts an array of `DetectorInterface` instances, runs them all, aggregates the results, and returns a final `AnalysisReport` DTO.

---

## Pages (Inertia/React)

### `resources/js/Pages/Analyser/Index.tsx`

The input page. Contains:

- A large `<textarea>` for pasting or typing text
- A live character count and word count displayed below the textarea, updating as the user types
- A submit button labelled **"Analyse Text"** that is disabled when the textarea is empty
- The form POSTs to `/analyse` via Inertia's `useForm` hook
- A loading/spinner state while the request is in flight

### `resources/js/Pages/Analyser/Results.tsx`

The results page. Receives the full `AnalysisReport` as Inertia props and renders:

1. **Overall score panel** — large prominent score (0–100) with colour coding:
   - Green background: score < 30 → label "Likely Human-Written"
   - Yellow background: score 30–59 → label "Possibly AI-Generated"
   - Red background: score ≥ 60 → label "Likely AI-Generated"

2. **Text statistics block** — word count, character count, average word length, analysis timestamp

3. **Highlighted text panel** — the original submitted text rendered with inline highlights (see Highlighting section below)

4. **Linguistic Factors section** — each factor displayed as a labelled row with a percentage/value, a coloured indicator bar, and an explanation

5. **Pattern Detections section** — each pattern category shown with occurrence count, score contribution out of its cap, and explanation

6. **Action buttons:**
   - "Copy Results" — copies a plain-text summary of the score, classification, and breakdown to the clipboard
   - "Analyse Another Text" — navigates back to `/` (use Inertia `router.visit`)

---

## Detector Implementations

### 1. AI Vocabulary Detector

**Class:** `AiVocabularyDetector`  
**Cap:** 15 points  
**Scoring:** 2 points per distinct term found, up to cap

Scan for these terms and phrases (case-insensitive, whole-word or whole-phrase matching):

> delve into, navigate, robust, innovative solutions, transformative, leverage, streamline, ecosystem, synergy, paradigm, cutting-edge, game-changer, holistic, foster, underscore, multifaceted, nuanced, pivotal, comprehensive, actionable insights, best practices, stakeholders, scalable, deep dive, at the end of the day, in today's world, in today's digital landscape, it is important to note, it is worth noting, in conclusion, to summarise

Track which distinct terms were matched and expose them in `matches`.

---

### 2. Structural Pattern Detectors

#### 2a. Rule of Three Detector

**Class:** `RuleOfThreeDetector`  
**Cap:** 10 points  
**Scoring:** 3 points per occurrence, up to cap

Detect comma-separated lists of exactly three parallel items using a regex that captures patterns like:
`[word/phrase], [word/phrase], and [word/phrase]`

Also detect three parallel adjectives or noun phrases before a noun.

#### 2b. Negative Parallelism Detector

**Class:** `NegativeParallelismDetector`  
**Cap:** 10 points  
**Scoring:** 4 points per occurrence, up to cap

Detect phrases matching:
- `not only ... but also`
- `neither ... nor` (formal usage)
- `both ... and` used in formal parallel construction

#### 2c. Outline-Style Conclusion Detector

**Class:** `OutlineConclusionDetector`  
**Cap:** 10 points  
**Scoring:** 5 points per occurrence, up to cap

Detect sentences matching the pattern:
`Despite [phrase], [subject] [verb] [benefit/opportunity phrase]`

Also detect closing sentences that begin with: "In conclusion,", "To summarise,", "Ultimately,", "In summary," followed by a broad claim.

#### 2d. False Range Detector

**Class:** `FalseRangeDetector`  
**Cap:** 8 points  
**Scoring:** 3 points per occurrence, up to cap

Detect `from X to Y` constructions. Flag them when X and Y are abstract or non-scalar concepts (i.e. not numbers, not times, not measurable units). Use a heuristic: if neither X nor Y contains a digit or a known unit word, flag it.

---

### 3. Vague Authority Detectors

#### 3a. Vague Attribution Detector

**Class:** `VagueAttributionDetector`  
**Cap:** 12 points  
**Scoring:** 3 points per distinct phrase type found, up to cap

Phrases to detect:
> experts agree, studies show, research indicates, research suggests, industry insiders report, according to experts, many experts believe, some researchers suggest, it has been shown, evidence suggests

Do **not** flag attributions that include a specific year, author name, or publication (e.g. "According to a 2024 study by Smith et al.").

#### 3b. Superficial Analysis Detector

**Class:** `SuperficialAnalysisDetector`  
**Cap:** 10 points  
**Scoring:** 2 points per occurrence, up to cap

Phrases to detect:
> it is worth noting, significant developments, one could argue, various sources indicate, it should be noted, it is important to consider, as previously mentioned, needless to say, it goes without saying

#### 3c. Overgeneralisation Detector

**Class:** `OvergeneralisationDetector`  
**Cap:** 10 points  
**Scoring:** 3 points per occurrence, up to cap

Phrases to detect:
> everyone knows, it is well established, universal consensus, widely accepted, it is commonly understood, it is generally agreed, most people would agree, without a doubt, undeniably

---

### 4. Promotional Language Detectors

#### 4a. Undue Emphasis Detector

**Class:** `UndueEmphasisDetector`  
**Cap:** 10 points  
**Scoring:** 2 points per term, up to cap

Terms to detect:
> tremendous, remarkable, groundbreaking, extraordinary, exceptional, incredible, outstanding, unparalleled, unprecedented, revolutionary, monumental, spectacular

Also flag multiple consecutive exclamation marks or ALL CAPS words longer than 3 characters.

#### 4b. Promotional Language Detector

**Class:** `PromotionalLanguageDetector`  
**Cap:** 10 points  
**Scoring:** 2 points per phrase, up to cap

Phrases to detect:
> game-changer, revolutionary, impressive features, transformative potential, cutting-edge solutions, industry-leading, world-class, best-in-class, next-generation, state-of-the-art, powerful solution

#### 4c. Elegant Variation Detector

**Class:** `ElegantVariationDetector`  
**Cap:** 8 points  
**Scoring:** 4 points per synonym cluster found cycling 3+ times, up to cap

Define synonym clusters to watch for:
- company / organisation / firm / enterprise / corporation / entity
- use / utilise / employ / leverage / apply
- show / demonstrate / illustrate / highlight / underscore / reveal
- problem / issue / challenge / concern / obstacle / difficulty
- help / assist / support / facilitate / enable / empower

For each cluster, count how many distinct members appear in the text. If 3 or more members of the same cluster appear, flag it as elegant variation.

---

### 5. Linguistic / Statistical Analysers

These live in `app/Services/AiDetector/Linguistic/` and return `LinguisticFactor` DTOs rather than scored detectors. Each factor has a label, raw value, formatted display value, a status (normal / warning / alert), and an explanation string.

**Important:** skip or mark as "insufficient data" any factor that requires more than the available text length (fewer than ~100 words).

#### 5a. Lexical Diversity

Calculate type-token ratio: `unique_words / total_words`.

- Normal range: 0.60–0.85 → status: normal
- Outside range → status: warning
- Display as a percentage

#### 5b. Sentence Length Variation

Split text into sentences. Calculate standard deviation and coefficient of variation (CV = stddev / mean).

- CV ≥ 0.45 → status: normal (naturally varied)
- CV 0.35–0.44 → status: warning
- CV < 0.35 → status: alert (unnaturally uniform — AI signal)

#### 5c. Passive Voice Frequency

Use a regex heuristic to detect passive constructions: forms of `to be` followed within 3 words by a past participle (words ending in `-ed`, `-en`, `-t`).

Calculate: `passive_sentences / total_sentences * 100`

- ≤ 10% → normal
- 10–15% → warning
- > 15% → alert

#### 5d. Transition Word Density

Detect formal discourse markers at sentence starts or after commas:
> furthermore, moreover, consequently, additionally, nevertheless, nonetheless, therefore, thus, hence, in contrast, on the other hand, as a result, in addition, by contrast, subsequently, accordingly

Calculate: `sentences_with_transition / total_sentences * 100`

- ≤ 10% → normal
- 10–20% → warning
- > 20% → alert

#### 5e. Flesch-Kincaid Grade Level

Use the standard FK formula:
`0.39 × (words/sentences) + 11.8 × (syllables/words) − 15.59`

Approximate syllable count by counting vowel groups per word.

- Grade < 12 → normal
- Grade 12–14 → warning
- Grade > 14 → alert (artificially complex)

#### 5f. Punctuation Patterns

Calculate per-sentence rates for: semicolons (`;`), em-dashes (`—` or ` -- `), colons (`:`), ellipses (`...` or `…`).

Flag: semicolons > 0.3 per sentence → alert. Em-dashes > 0.4 per sentence → alert.

#### 5g. Rare Word Usage

Define rare words as those not in a bundled list of the ~5,000 most common English words (include a plain-text file at `resources/data/common_words.txt`). 

Calculate: `rare_words / total_words * 100`

- 3–8% → normal
- 8–12% → warning
- > 12% → alert

#### 5h. Zipf's Law Comparison (Advanced)

Rank all words in the text by frequency. For natural language, frequency should approximate `1/rank`. Calculate the R² of the actual distribution against the ideal Zipf curve.

- R² > 0.92 → normal
- R² 0.85–0.92 → warning
- R² < 0.85 → alert (unusual distribution)

Display as an R² value with explanation.

---

### 6. Scoring Aggregation

**Class:** `ScoreAggregator`

Sum all scored detector contributions. Linguistic factors contribute to the score as follows:

| Factor | Alert contribution | Warning contribution |
|---|---|---|
| Sentence length variation | 8 pts | 4 pts |
| Transition word density | 8 pts | 4 pts |
| Passive voice frequency | 6 pts | 3 pts |
| Flesch-Kincaid grade | 5 pts | 2 pts |
| Lexical diversity | 5 pts | 2 pts |
| Punctuation patterns | 4 pts | 2 pts |
| Rare word usage | 5 pts | 2 pts |
| Zipf's Law | 4 pts | 2 pts |

If the raw total exceeds 100, normalise all contributions proportionally so the final score is exactly 100. Clamp to [0, 100].

**Classification:**
- < 30 → "Likely Human-Written"
- 30–59 → "Possibly AI-Generated"
- ≥ 60 → "Likely AI-Generated"

---

## Text Highlighting

On the Results page, render the original text with inline `<mark>` spans around detected pattern matches.

Rules:
- Each detector category gets a distinct highlight colour (use Tailwind `bg-yellow-200`, `bg-blue-200`, `bg-red-200`, `bg-green-200`, `bg-purple-200`, `bg-orange-200` etc. — assign one colour per category)
- When matches from different detectors overlap in the source text, keep the first match applied and skip subsequent overlaps
- Build the highlight map server-side: `AnalysisService` should return a `highlights` array of `{ start: int, end: int, category: string, colour: string }` objects sorted by `start`
- On the React side, walk through the highlights array and split the original text string into alternating plain and highlighted segments
- Render a colour legend / badge row above the highlighted text showing which colour corresponds to which category

---

## AnalysisReport DTO

The `AnalysisReport` returned from `AnalysisService` and passed as Inertia props should contain:

```php
class AnalysisReport
{
    public string $text;               // original submitted text
    public int $wordCount;
    public int $characterCount;
    public float $averageWordLength;
    public string $analysedAt;         // ISO 8601 timestamp

    public int $totalScore;            // 0–100
    public string $classification;     // "Likely Human-Written" etc.
    public string $classificationColour; // "green" | "yellow" | "red"

    /** @var DetectorResult[] */
    public array $detectorResults;

    /** @var LinguisticFactor[] */
    public array $linguisticFactors;

    /** @var array{ start: int, end: int, category: string, colour: string }[] */
    public array $highlights;
}
```

---

## Copy Results Feature

When the user clicks "Copy Results", write to the clipboard a plain-text string in this format:

```
AI Text Detector — Analysis Report
Analysed: [timestamp]

RESULT: [classification] ([score]/100)

--- LINGUISTIC FACTORS ---
[factor label]: [value] ([status])

--- PATTERN DETECTIONS ---
[category]: [occurrences] occurrences, [score]/[cap] pts
  [explanation]

```

Use the browser `navigator.clipboard.writeText()` API. Show a brief "Copied!" confirmation tooltip or button state change.

---

## Validation

In `AnalyserController@analyse`, validate:
- `text` is required
- `text` is a string
- `text` minimum length: 1 character
- `text` maximum length: 50,000 characters

Return Inertia validation errors to the Index page if validation fails.

---

## Error Handling

- If text is too short for a linguistic factor to be meaningful (< 50 words), return the factor with status `"insufficient"` and display "Not enough text" rather than a misleading value
- Wrap the entire analysis pipeline in a try/catch; on unexpected errors return a generic error state to the Results page

---

## File Structure Summary

```
app/
  Http/Controllers/AnalyserController.php
  Services/AiDetector/
    AnalysisService.php
    ScoreAggregator.php
    DetectorInterface.php
    DTOs/
      DetectorResult.php
      LinguisticFactor.php
      AnalysisReport.php
    Detectors/
      AiVocabularyDetector.php
      RuleOfThreeDetector.php
      NegativeParallelismDetector.php
      OutlineConclusionDetector.php
      FalseRangeDetector.php
      VagueAttributionDetector.php
      SuperficialAnalysisDetector.php
      OvergeneralisationDetector.php
      UndueEmphasisDetector.php
      PromotionalLanguageDetector.php
      ElegantVariationDetector.php
    Linguistic/
      LexicalDiversityAnalyser.php
      SentenceLengthAnalyser.php
      PassiveVoiceAnalyser.php
      TransitionWordAnalyser.php
      FleschKincaidAnalyser.php
      PunctuationAnalyser.php
      RareWordAnalyser.php
      ZipfAnalyser.php

resources/
  data/
    common_words.txt
  js/
    Pages/Analyser/
      Index.tsx
      Results.tsx
    Components/
      ScoreBadge.tsx
      HighlightedText.tsx
      LinguisticFactorRow.tsx
      DetectorResultRow.tsx
      CopyButton.tsx
```

---

## Notes for Claude Code

- Do not use a database — all analysis is stateless
- Do not install any NLP or ML packages; all analysis should be pure PHP regex and string functions
- The `common_words.txt` file should contain one lowercase word per line; generate a reasonable 5,000-word list or use a public domain word frequency list
- Prefer readability over cleverness in the detector implementations — these will need to be tuned
- Use PHP 8.2+ features (readonly classes, constructor property promotion, named arguments) freely
- All React components should be typed with TypeScript interfaces matching the PHP DTOs
- Do not add authentication — this is a single-user dev tool
