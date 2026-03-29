# ChatGPT Fixtures — COP21 Accuracy Test Set

This directory holds AI-generated fixture files produced by ChatGPT (GPT-4o or later).
They are used by `php artisan accuracy:report` and `tests/Accuracy/AccuracyTest.php`
to measure how well the detector identifies AI-written text.

---

## JSON format

Each fixture is a single `.json` file with the following structure:

```json
{
  "id": "cop21-01-overview",
  "topic": "Paris Climate Agreement — Overview",
  "angle": 1,
  "source": "ChatGPT, GPT-4o, 2024",
  "label": "ai",
  "model": "gpt-4o",
  "text": "Paste the generated text here."
}
```

| Field    | Description                                                               |
|----------|---------------------------------------------------------------------------|
| `id`     | Unique kebab-case identifier. Follow the naming convention below.         |
| `topic`  | Human-readable title for this angle.                                      |
| `angle`  | Integer 1–10 matching the angle list below.                               |
| `source` | Free-text provenance note, e.g. `"ChatGPT, GPT-4o, 2024"`.              |
| `label`  | Always `"ai"` for files in this directory.                                |
| `model`  | The exact model used, e.g. `"gpt-4o"` or `"gpt-4o-mini"`.               |
| `text`   | The raw generated text. 2–4 paragraphs is ideal (150–400 words).         |

---

## Filename convention

```
cop21-{nn}-{slug}.json
```

- `{nn}` — zero-padded two-digit angle number, e.g. `01`, `10`
- `{slug}` — short kebab-case label matching the angle title below

Examples:
```
cop21-01-overview.json
cop21-02-negotiations.json
cop21-03-temperature-targets.json
```

---

## The 10 angles

Generate one fixture per angle to get a balanced test set of 10 samples.

| # | Slug                    | Full title                                                        |
|---|-------------------------|-------------------------------------------------------------------|
| 1 | `overview`              | Overview of the Paris Agreement and its significance              |
| 2 | `negotiations`          | The COP21 negotiation process and key actors                      |
| 3 | `temperature-targets`   | The 1.5 °C and 2 °C temperature targets explained                |
| 4 | `ndcs`                  | Nationally Determined Contributions (NDCs) and accountability     |
| 5 | `finance`               | Climate finance: the $100 billion annual pledge                   |
| 6 | `developing-nations`    | Impact on and obligations of developing nations                   |
| 7 | `loss-and-damage`       | Loss and damage provisions for vulnerable countries               |
| 8 | `us-withdrawal`         | The United States withdrawal and re-entry under Biden             |
| 9 | `progress`              | Progress since Paris: emissions trends and stocktake results      |
|10 | `criticism`             | Criticism of the agreement — ambition gaps and enforcement limits |

---

## Label and model values

- `"label"` must always be `"ai"` for every file in this directory.
- `"model"` should reflect the exact model version used:
  - `"gpt-4o"` for GPT-4o (recommended)
  - `"gpt-4o-mini"` if the mini variant was used
  - `"gpt-4-turbo"` for older GPT-4 Turbo outputs
- If you regenerate a fixture with a different model, update the `model` field
  and note it in the `source` field.

---

## Suggested prompt

Use this prompt in ChatGPT when generating each fixture. Replace `[angle title]`
with the full title from the table above.

```
Write 2-3 paragraphs about [angle title] regarding the Paris Climate Agreement
(COP21) of December 2015, in a neutral analytical style. Do not use bullet
points or headings — write in continuous prose only.
```

Example for angle 1:

> Write 2-3 paragraphs about the Overview of the Paris Agreement and its
> significance regarding the Paris Climate Agreement (COP21) of December 2015,
> in a neutral analytical style. Do not use bullet points or headings — write
> in continuous prose only.

Paste the full response into the `"text"` field of the JSON file.

---

## Quick checklist before committing a fixture

- [ ] Filename matches `cop21-{nn}-{slug}.json`
- [ ] `"label"` is `"ai"` (not `"human"`)
- [ ] `"model"` is filled in
- [ ] `"text"` is non-empty and contains the raw prose (no Markdown formatting)
- [ ] `"angle"` integer matches the filename number
- [ ] JSON is valid (run `php -r "echo json_decode(file_get_contents('cop21-01-overview.json')) ? 'OK' : 'INVALID';"`
      or use a JSON linter)
