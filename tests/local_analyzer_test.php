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
 * Deterministic analyzer tests.
 *
 * @package   local_plainlanguage
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_plainlanguage;

use advanced_testcase;
use local_plainlanguage\analysis\local_analyzer;

/**
 * Tests local heuristics.
 *
 * @covers \local_plainlanguage\analysis\local_analyzer
 */
final class local_analyzer_test extends advanced_testcase {
    /** Local checks should flag measurable structure/style issues without AI. */
    public function test_detects_local_findings(): void {
        $longsentence = implode(' ', array_fill(0, 35, 'palavra')) . '.';
        $html = '<p>' . $longsentence . '</p>'
            . '<p>IMPORTANTE LEIA ENVIE COMPLETE RESPOSTA AGORA.</p>'
            . '<p><a href="https://example.test/manual">clique aqui</a></p>';

        $result = (new local_analyzer())->analyze($html);
        $codes = array_column($result['findings'], 'code');

        $this->assertContains('long_sentence', $codes);
        $this->assertContains('vague_link', $codes);
        $this->assertGreaterThan(0, $result['metrics']['wordcount']);
        $this->assertSame(1, $result['metrics']['linkcount']);
    }

    /** Plain text extraction must remove markup but keep block boundaries. */
    public function test_plain_text_extraction(): void {
        $text = local_analyzer::to_plain_text('<p>Primeiro.</p><p>Segundo <strong>passo</strong>.</p>');
        $this->assertStringNotContainsString('<strong>', $text);
        $this->assertStringContainsString('Primeiro.', $text);
        $this->assertStringContainsString('Segundo passo.', $text);
    }
}
