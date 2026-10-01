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
 * Protect HTML structure and placeholders before an AI rewrite.
 *
 * @package   local_plainlanguage
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_plainlanguage\content;

use moodle_exception;

/**
 * Tokenizes tags and placeholders so the AI may rewrite only visible text.
 */
class html_protector {
    /** Token prefix. */
    private const PREFIX = '__PLAINLANG_PROTECTED_';

    /**
     * Replace tags and common Moodle/template placeholders by opaque tokens.
     *
     * @param string $html Source HTML.
     * @return array{content:string,tokens:array<string,string>,sequence:string[]}
     */
    public static function protect(string $html): array {
        $tokens = [];
        $sequence = [];
        $counter = 0;

        $replace = static function (array $match) use (&$tokens, &$sequence, &$counter): string {
            $token = self::PREFIX . str_pad((string)$counter++, 6, '0', STR_PAD_LEFT) . '__';
            $tokens[$token] = $match[0];
            $sequence[] = $token;
            return $token;
        };

        $protected = preg_replace_callback('/<[^>]+>/su', $replace, $html) ?? $html;
        $protected = preg_replace_callback(
            '/@@[A-Z0-9_:\-]+@@|\{\{[^{}\r\n]+\}\}|\[\[[^\[\]\r\n]+\]\]/u',
            $replace,
            $protected
        ) ?? $protected;

        usort($sequence, static function (string $left, string $right) use ($protected): int {
            return strpos($protected, $left) <=> strpos($protected, $right);
        });

        return [
            'content' => $protected,
            'tokens' => $tokens,
            'sequence' => $sequence,
        ];
    }

    /**
     * Restore protected tokens after validating exact presence and order.
     *
     * @param string $rewritten AI-returned tokenized text.
     * @param array<string,string> $tokens Token map.
     * @param string[] $sequence Original token order.
     * @return string
     */
    public static function restore(string $rewritten, array $tokens, array $sequence): string {
        $lastposition = -1;
        foreach ($sequence as $token) {
            if (!array_key_exists($token, $tokens) || substr_count($rewritten, $token) !== 1) {
                throw new moodle_exception('invalidairesponse', 'local_plainlanguage');
            }
            $position = strpos($rewritten, $token);
            if ($position === false || $position <= $lastposition) {
                throw new moodle_exception('invalidairesponse', 'local_plainlanguage');
            }
            $lastposition = $position;
        }

        if (preg_match('/' . preg_quote(self::PREFIX, '/') . '\d{6}__/', str_replace($sequence, '', $rewritten))) {
            throw new moodle_exception('invalidairesponse', 'local_plainlanguage');
        }

        // Do not allow the model to add new markup around the opaque tokens.
        if (strip_tags($rewritten) !== $rewritten) {
            throw new moodle_exception('invalidairesponse', 'local_plainlanguage');
        }

        return strtr($rewritten, $tokens);
    }
}
