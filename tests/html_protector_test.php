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
 * HTML protection tests.
 *
 * @package   local_plainlanguage
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_plainlanguage;

use advanced_testcase;
use local_plainlanguage\content\html_protector;
use moodle_exception;

/**
 * Tests for HTML/token preservation.
 *
 * @covers \local_plainlanguage\content\html_protector
 */
final class html_protector_test extends advanced_testcase {
    /** Tokens must restore exact tags, attributes, URLs and placeholders. */
    public function test_preserves_html_and_placeholders(): void {
        $html = '<p>Read <a href="@@PLUGINFILE@@/guide.pdf" class="btn">this guide</a> {{name}}.</p>';
        $protected = html_protector::protect($html);
        $rewritten = str_replace(['Read ', 'this guide'], ['Please read ', 'the guide'], $protected['content']);
        $restored = html_protector::restore($rewritten, $protected['tokens'], $protected['sequence']);

        $this->assertStringContainsString('<p>', $restored);
        $this->assertStringContainsString('href="@@PLUGINFILE@@/guide.pdf"', $restored);
        $this->assertStringContainsString('{{name}}', $restored);
        $this->assertStringContainsString('Please read ', $restored);
        $this->assertStringContainsString('the guide', $restored);
    }

    /** Missing a protected token invalidates the AI rewrite. */
    public function test_rejects_missing_token(): void {
        $protected = html_protector::protect('<p>Hello <strong>world</strong></p>');
        $rewritten = str_replace($protected['sequence'][1], '', $protected['content']);
        $this->expectException(moodle_exception::class);
        html_protector::restore($rewritten, $protected['tokens'], $protected['sequence']);
    }

    /** New model-generated HTML is rejected. */
    public function test_rejects_new_markup(): void {
        $protected = html_protector::protect('<p>Hello</p>');
        $rewritten = '<script>alert(1)</script>' . $protected['content'];
        $this->expectException(moodle_exception::class);
        html_protector::restore($rewritten, $protected['tokens'], $protected['sequence']);
    }
}
