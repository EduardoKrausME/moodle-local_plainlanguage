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
 * Strict JSON response parser and sanitizer.
 *
 * @package   local_plainlanguage
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_plainlanguage\ai;

use JsonException;
use local_plainlanguage\exception\ai_exception;

/**
 * Validate the fixed schemas expected from AI Bridge responses.
 */
class json_parser {
    /** Allowed semantic finding categories. */
    private const CATEGORIES = [
        'ambiguity',
        'incomplete_instruction',
        'confusing_implicit_subject',
        'unexplained_prerequisite',
        'undefined_term',
        'multiple_interpretations',
        'confusing_sequence',
        'overloaded_instruction',
        'terminology_inconsistency',
    ];

    /**
     * Parse review JSON.
     *
     * @param string $text Raw model response.
     * @return array{summary:string,findings:array<int,array{category:string,excerpt:string,problem:string,
     *     why_confusing:string,suggestion:string}>}
     */
    public static function parse_review(string $text): array {
        $data = self::decode($text);
        if (!array_key_exists('summary', $data) || !is_string($data['summary']) ||
            !array_key_exists('findings', $data) || !is_array($data['findings'])) {
            throw new ai_exception('invalidairesponse', 'local_plainlanguage');
        }
        if (count($data['findings']) > 50) {
            throw new ai_exception('invalidairesponse', 'local_plainlanguage');
        }

        $findings = [];
        foreach ($data['findings'] as $finding) {
            if (!is_array($finding)) {
                throw new ai_exception('invalidairesponse', 'local_plainlanguage');
            }
            foreach (['category', 'excerpt', 'problem', 'why_confusing', 'suggestion'] as $required) {
                if (!array_key_exists($required, $finding) || !is_string($finding[$required])) {
                    throw new ai_exception('invalidairesponse', 'local_plainlanguage');
                }
            }
            if (!in_array($finding['category'], self::CATEGORIES, true)) {
                throw new ai_exception('invalidairesponse', 'local_plainlanguage');
            }
            $findings[] = [
                'category' => $finding['category'],
                'excerpt' => self::clean_text($finding['excerpt'], 1000),
                'problem' => self::clean_text($finding['problem'], 2000),
                'why_confusing' => self::clean_text($finding['why_confusing'], 3000),
                'suggestion' => self::clean_text($finding['suggestion'], 4000),
            ];
        }

        return [
            'summary' => self::clean_text($data['summary'], 4000),
            'findings' => $findings,
        ];
    }

    /**
     * Parse rewrite JSON. HTML safety is validated later by html_protector.
     *
     * @param string $text Raw model response.
     * @return array{rewritten:string,notes:string[]}
     */
    public static function parse_rewrite(string $text): array {
        $data = self::decode($text);
        if (!isset($data['rewritten']) || !is_string($data['rewritten'])) {
            throw new ai_exception('invalidairesponse', 'local_plainlanguage');
        }
        $notes = [];
        if (isset($data['notes'])) {
            if (!is_array($data['notes']) || count($data['notes']) > 20) {
                throw new ai_exception('invalidairesponse', 'local_plainlanguage');
            }
            foreach ($data['notes'] as $note) {
                if (!is_string($note)) {
                    throw new ai_exception('invalidairesponse', 'local_plainlanguage');
                }
                $notes[] = self::clean_text($note, 2000);
            }
        }
        return [
            'rewritten' => $data['rewritten'],
            'notes' => $notes,
        ];
    }

    /**
     * Decode a strict JSON object. Markdown fences and prose wrappers are rejected.
     *
     * @param string $text Raw text.
     * @return array<string,mixed>
     */
    private static function decode(string $text): array {
        $text = trim($text);
        try {
            $data = json_decode($text, true, 64, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new ai_exception('invalidairesponse', 'local_plainlanguage', '', null, $e->getMessage());
        }
        if (!is_array($data) || array_is_list($data)) {
            throw new ai_exception('invalidairesponse', 'local_plainlanguage');
        }
        return $data;
    }

    /**
     * Strip markup/control characters and cap model-controlled display strings.
     *
     * @param string $text Text.
     * @param int $maxlength Maximum characters.
     * @return string
     */
    private static function clean_text(string $text, int $maxlength): string {
        $text = strip_tags($text);
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text) ?? '';
        $text = trim($text);
        if (mb_strlen($text) > $maxlength) {
            $text = mb_substr($text, 0, $maxlength);
        }
        return $text;
    }
}
