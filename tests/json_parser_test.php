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
 * AI JSON schema and sanitization tests.
 *
 * @package   local_plainlanguage
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_plainlanguage;

use advanced_testcase;
use local_plainlanguage\ai\json_parser;
use local_plainlanguage\exception\ai_exception;

/**
 * Tests strict JSON parsing, suggestions and sanitization.
 *
 * @covers \local_plainlanguage\ai\json_parser
 */
final class json_parser_test extends advanced_testcase {
    /**
     * A valid suggestion must preserve the fixed schema.
     */
    public function test_parses_suggestions(): void {
        $json = json_encode([
            'summary' => 'Há duas instruções que podem ser mais explícitas.',
            'findings' => [[
                'category' => 'ambiguity',
                'excerpt' => 'Envie o arquivo depois.',
                'problem' => '“Depois” não indica depois de qual ação.',
                'why_confusing' => 'Existem duas sequências plausíveis.',
                'suggestion' => 'Indique a ação anterior ou o momento esperado.',
            ]],
        ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        $parsed = json_parser::parse_review($json);
        $this->assertCount(1, $parsed['findings']);
        $this->assertSame('ambiguity', $parsed['findings'][0]['category']);
        $this->assertStringContainsString('momento', $parsed['findings'][0]['suggestion']);
    }

    /**
     * Malformed JSON must not be guessed or partially accepted.
     */
    public function test_rejects_malformed_json(): void {
        $this->expectException(ai_exception::class);
        json_parser::parse_review('{"summary":"x","findings":[');
    }

    /**
     * Model-controlled display strings are stripped of markup.
     */
    public function test_sanitizes_model_strings(): void {
        $json = json_encode([
            'summary' => '<script>alert(1)</script><b>Resumo</b>',
            'findings' => [[
                'category' => 'undefined_term',
                'excerpt' => '<img src=x onerror=alert(1)>API',
                'problem' => '<b>Termo não definido</b>',
                'why_confusing' => '<i>Não há explicação</i>',
                'suggestion' => '<a href="javascript:alert(1)">Defina o termo</a>',
            ]],
        ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        $parsed = json_parser::parse_review($json);
        $this->assertStringNotContainsString('<', $parsed['summary']);
        $this->assertStringNotContainsString('<', $parsed['findings'][0]['suggestion']);
        $this->assertStringContainsString('Defina o termo', $parsed['findings'][0]['suggestion']);
    }

    /**
     * Unknown categories are schema violations, even when JSON is syntactically valid.
     */
    public function test_rejects_unknown_category(): void {
        $json = json_encode([
            'summary' => 'x',
            'findings' => [[
                'category' => 'made_up_score',
                'excerpt' => '',
                'problem' => 'x',
                'why_confusing' => 'x',
                'suggestion' => 'x',
            ]],
        ], JSON_THROW_ON_ERROR);
        $this->expectException(ai_exception::class);
        json_parser::parse_review($json);
    }
}
