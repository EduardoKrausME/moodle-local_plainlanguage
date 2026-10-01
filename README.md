# Moodle local_plainlanguage

`local_plainlanguage` audits the clarity of teacher-authored Moodle content without turning an AI model into an
automatic editor. The plugin deliberately separates deterministic checks from semantic analysis: PHP handles what can be
measured reliably from HTML/text, while AI is reserved for ambiguity, missing context, unclear sequencing and similar
meaning-level problems.

The plugin never saves an AI rewrite automatically. A teacher can request a full rewrite suggestion, compare it side by
side with the original and copy it manually if useful.

## Requirements

- Moodle 4.5 or later.
- PHP supported by the target Moodle version. Moodle 4.5 requires PHP 8.1+.
- `local_ai_bridge` version `2026093001` or later:
  https://github.com/EduardoKrausME/moodle-local_ai_bridge/

`version.php` declares the dependency explicitly:

```php
$plugin->dependencies = [
    'local_ai_bridge' => 2026093001,
];
```

There are no API keys, provider endpoints or model settings in this plugin. Every AI request goes exclusively through:

```php
\local_ai_bridge\api::generate()
```

using the purpose:

```text
plainlanguage-review
```

That purpose must be configured and routed in AI Bridge for the tenant/user performing the review.

## Capability

The review UI requires:

```text
local/plainlanguage:review
```

The capability is course-scoped and is granted by default to `editingteacher` and `manager` archetypes.

## Supported teacher-authored content

The first version extracts only course/content fields written by teachers:

- Page content;
- Book chapters;
- Assignment introduction/instructions;
- Forum description;
- section summaries;
- Label / Text and media area content.

The extractor does **not** query student submissions, forum posts, grades, feedback responses, user profiles or other
learner records. A review request therefore contains only the selected teacher-authored content and local metrics
derived from it.

## Deterministic checks first

`classes/analysis/local_analyzer.php` calculates local heuristics including:

- word and heuristic sentence counts;
- average and maximum sentence length;
- long sentences;
- large paragraphs;
- proportion of uppercase words;
- likely instruction count when recognizable instruction verbs are present;
- vague link text such as `clique aqui` / `click here`;
- link and heading counts;
- empty headings;
- long content with no headings.

These values are intentionally labelled as heuristics. The plugin does not present a readability formula or threshold as
an objective measure of whether text is “good”.

## Semantic AI review

AI receives plain text, content type/title and deterministic metrics, then returns JSON only. It is instructed to report
concrete findings in these categories:

- `ambiguity`;
- `incomplete_instruction`;
- `confusing_implicit_subject`;
- `unexplained_prerequisite`;
- `undefined_term`;
- `multiple_interpretations`;
- `confusing_sequence`;
- `overloaded_instruction`;
- `terminology_inconsistency`.

Every finding must contain:

```json
{
  "category": "ambiguity",
  "excerpt": "...",
  "problem": "...",
  "why_confusing": "...",
  "suggestion": "..."
}
```

The parser accepts a JSON object only: Markdown fences or prose wrappers are rejected, as are malformed JSON, missing
required fields, unknown categories and excessive item counts. Model-controlled display strings are stripped of
HTML/control characters before rendering.

## Rewrite suggestions and HTML preservation

The `Sugerir reescrita` action is intentionally separate from the audit. Before a rewrite request, `html_protector`
replaces HTML tags and common Moodle/template placeholders with opaque tokens such as:

```text
__PLAINLANG_PROTECTED_000001__
```

The model is allowed to rewrite only the visible text between those tokens. The response is rejected unless every
protected token is returned exactly once and in exactly the original order. New model-generated HTML markup is also
rejected.

This means attributes, links, `@@PLUGINFILE@@` references and protected placeholders are not handed to the model as
editable text. The result is still passed through Moodle formatting/cleaning for display, and there is deliberately no
save/update endpoint in this plugin.

## Cache

AI results are cached by SHA-256 over the prompt/schema version, purpose, language, content type/key and content. The
cache uses Moodle `MODE_SESSION`, so identical content can avoid repeated calls during the current teacher session
without allowing one tenant/user session to reuse another tenant's AI response or bypass its routing/credit rules.

Selecting `Ignorar revisão em cache` forces a fresh semantic review.

## Error handling

AI Bridge failures are converted to a safe generic message for the teacher while technical details are emitted only
through Moodle developer debugging. Typical causes include:

- tenant not configured or disabled;
- `plainlanguage-review` purpose missing/disabled;
- no route/model configured for the logical role;
- missing `local/ai_bridge:use` capability;
- exhausted credits;
- all configured providers failing.

A bridge failure does not discard deterministic findings for the selected item.

## Tests

The PHPUnit suite covers:

- extraction of supported content types;
- invalid content keys;
- deterministic analyzer behavior;
- HTML/tag/URL/placeholder preservation;
- rejection of missing tokens and injected markup;
- valid semantic suggestions;
- malformed AI JSON;
- model-output sanitization;
- rejection of schema drift/unknown categories;
- capability registration.

## CI

`.github/workflows/ci.yml` runs against Moodle 4.5/PHP 8.1 and Moodle 5.2/PHP 8.3 on PostgreSQL and MariaDB. It
installs `local_ai_bridge` as an extra plugin dependency and runs:

- PHP lint;
- `EduardoKrausME/moodle-plugin-validate`;
- Moodle plugin validation;
- Moodle Code Checker;
- Mustache lint;
- PHPUnit.

## Installation

Copy the plugin to:

```text
local/plainlanguage
```

Then run the normal Moodle upgrade process. Configure the `plainlanguage-review` purpose/routes in AI Bridge before
using semantic review.

## License

GNU GPL v3 or later.
