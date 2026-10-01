<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * AI Bridge client for semantic plain-language checks.
 *
 * @package   local_plainlanguage
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_plainlanguage\ai;

use cache;
use local_ai_bridge\api;
use local_plainlanguage\content\content_item;
use local_plainlanguage\content\html_protector;
use local_plainlanguage\exception\ai_exception;
use Throwable;

/**
 * All AI calls for this plugin are centralized here and use local_ai_bridge only.
 */
class client {
    /** AI Bridge purpose id. */
    public const PURPOSE = 'plainlanguage-review';

    /** Prompt/cache schema version. */
    private const SCHEMA_VERSION = '2026093001';

    /** @var cache Moodle cache instance. */
    private cache $cache;

    /**
     * Initialise the review cache.
     */
    public function __construct() {
        $this->cache = cache::make('local_plainlanguage', 'reviews');
    }

    /**
     * Run semantic review.
     *
     * @param content_item $item Content item.
     * @param string $plaintext Plain text extracted locally.
     * @param array<string,int|float> $metrics Deterministic metrics for context only.
     * @param bool $ignorecache Whether to bypass current-session cache.
     * @return array{summary:string,findings:array,cached:bool}
     */
    public function review(content_item $item, string $plaintext, array $metrics, bool $ignorecache = false): array {
        $cachekey = $this->cache_key('review', $item, $plaintext);
        if (!$ignorecache) {
            $cached = $this->cache->get($cachekey);
            if (is_array($cached)) {
                $cached['cached'] = true;
                return $cached;
            }
        }

        $payload = [
            'task' => 'plain_language_semantic_review',
            'content_type' => $item->type,
            'title' => $item->title,
            'local_metrics' => $metrics,
            'content' => $plaintext,
        ];

        $prompt = <<<'PROMPT'
Review only the semantic clarity of the teacher-authored content in INPUT_JSON.
Do not produce a full rewrite. Do not score the teacher or content. Do not infer anything about students.
Do not repeat deterministic style checks such as sentence length, paragraph size, uppercase ratio, or vague-link detection.
Identify only these categories when there is a concrete issue:
- ambiguity
- incomplete_instruction
- confusing_implicit_subject
- unexplained_prerequisite
- undefined_term
- multiple_interpretations
- confusing_sequence
- overloaded_instruction
- terminology_inconsistency

Return JSON only, with exactly this shape:
{
  "summary": "short neutral summary",
  "findings": [
    {
      "category": "one_of_the_categories_above",
      "excerpt": "short exact or near-exact excerpt",
      "problem": "specific problem",
      "why_confusing": "why a learner could reasonably misunderstand it",
      "suggestion": "specific improvement suggestion, not a forced full rewrite"
    }
  ]
}

Use the predominant language of the supplied content for all values.
If there are no semantic findings, return an empty findings array.
INPUT_JSON:
PROMPT;
        $prompt .= json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        $raw = $this->generate($prompt);
        $parsed = json_parser::parse_review($raw);
        $result = [
            'summary' => $parsed['summary'],
            'findings' => $parsed['findings'],
            'cached' => false,
        ];
        $this->cache->set($cachekey, $result);
        return $result;
    }

    /**
     * Suggest a rewrite without changing HTML structure or placeholders.
     *
     * @param content_item $item Content item.
     * @param bool $ignorecache Whether to bypass cache.
     * @return array{html:string,notes:string[],cached:bool}
     */
    public function rewrite(content_item $item, bool $ignorecache = false): array {
        $cachekey = $this->cache_key('rewrite', $item, $item->html);
        if (!$ignorecache) {
            $cached = $this->cache->get($cachekey);
            if (is_array($cached)) {
                $cached['cached'] = true;
                return $cached;
            }
        }

        $protected = html_protector::protect($item->html);
        $payload = [
            'task' => 'plain_language_rewrite_suggestion',
            'content_type' => $item->type,
            'title' => $item->title,
            'protected_content' => $protected['content'],
        ];

        $prompt = <<<'PROMPT'
Suggest a clearer version of the teacher-authored content in INPUT_JSON while preserving its meaning and requirements.
This is a suggestion for a teacher, not an automatic edit.
Do not add facts, prerequisites, deadlines, grading rules, links, or requirements that are not present.

The protected_content contains opaque tokens such as __PLAINLANG_PROTECTED_000001__.
Every protected token MUST appear exactly once in the output, in exactly the same order.
Do not edit, remove, duplicate, rename, or move tokens.
Do not add any HTML tags; HTML is represented by protected tokens.

Return JSON only:
{
  "rewritten": "the complete rewritten protected_content",
  "notes": ["short explanation of an important clarity change"]
}

Use the predominant language of the supplied content.
Keep terminology consistent and prefer explicit actors, complete instructions and a clear sequence.
INPUT_JSON:
PROMPT;
        $prompt .= json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        $raw = $this->generate($prompt);
        $parsed = json_parser::parse_rewrite($raw);
        try {
            $restored = html_protector::restore($parsed['rewritten'], $protected['tokens'], $protected['sequence']);
        } catch (Throwable $e) {
            if ($e instanceof ai_exception) {
                throw $e;
            }
            throw new ai_exception('invalidairesponse', 'local_plainlanguage', '', null, $e->getMessage());
        }

        $result = [
            'html' => $restored,
            'notes' => $parsed['notes'],
            'cached' => false,
        ];
        $this->cache->set($cachekey, $result);
        return $result;
    }

    /**
     * Call AI Bridge and return response text.
     *
     * @param string $prompt Prompt.
     * @return string
     */
    protected function generate(string $prompt): string {
        if (!class_exists('\\local_ai_bridge\\api')) {
            throw new ai_exception('bridgeerror', 'local_plainlanguage');
        }

        try {
            $response = api::generate(
                self::PURPOSE,
                [
                    ['role' => 'user', 'content' => $prompt],
                ]
            );
            return $response->text;
        } catch (Throwable $e) {
            debugging('local_plainlanguage AI Bridge error: ' . $e->getMessage(), DEBUG_DEVELOPER);
            throw new ai_exception('bridgeerror', 'local_plainlanguage', '', null, $e->getMessage());
        }
    }

    /**
     * Build a content hash. MODE_SESSION cache keeps identical hashes isolated per logged-in session.
     *
     * @param string $mode Review/rewrite mode.
     * @param content_item $item Item.
     * @param string $content Input content.
     * @return string
     */
    private function cache_key(string $mode, content_item $item, string $content): string {
        return hash('sha256', implode("\0", [
            self::SCHEMA_VERSION,
            self::PURPOSE,
            $mode,
            current_language(),
            $item->type,
            $item->key,
            $content,
        ]));
    }
}
