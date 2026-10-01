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
 * Content extractor tests.
 *
 * @package   local_plainlanguage
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_plainlanguage;

use advanced_testcase;
use local_plainlanguage\content\extractor;
use moodle_exception;

/**
 * Ensure only supported teacher-authored fields are extracted.
 *
 * @covers \local_plainlanguage\content\extractor
 */
final class extractor_test extends advanced_testcase {
    /**
     * Extract Page, Book, Assignment, Forum, Section and Label/Text content.
     */
    public function test_extracts_supported_content(): void {
        global $DB;
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course(['numsections' => 1]);
        $section = $DB->get_record('course_sections', ['course' => $course->id, 'section' => 1], '*', MUST_EXIST);
        $section->summary = '<p>Resumo da seção para revisão.</p>';
        $section->summaryformat = FORMAT_HTML;
        $DB->update_record('course_sections', $section);

        $this->getDataGenerator()->create_module('page', [
            'course' => $course->id,
            'name' => 'Página',
            'content' => '<p>Conteúdo da página.</p>',
            'contentformat' => FORMAT_HTML,
        ]);
        $this->getDataGenerator()->create_module('assign', [
            'course' => $course->id,
            'name' => 'Tarefa',
            'intro' => '<p>Envie o relatório.</p>',
            'introformat' => FORMAT_HTML,
        ]);
        $this->getDataGenerator()->create_module('forum', [
            'course' => $course->id,
            'name' => 'Fórum',
            'intro' => '<p>Discuta o caso.</p>',
            'introformat' => FORMAT_HTML,
        ]);
        $this->getDataGenerator()->create_module('label', [
            'course' => $course->id,
            'name' => 'Texto',
            'intro' => '<p>Texto e mídia.</p>',
            'introformat' => FORMAT_HTML,
        ]);
        $book = $this->getDataGenerator()->create_module('book', [
            'course' => $course->id,
            'name' => 'Livro',
        ]);
        $DB->insert_record('book_chapters', (object)[
            'bookid' => $book->id,
            'pagenum' => 1,
            'subchapter' => 0,
            'title' => 'Capítulo 1',
            'content' => '<p>Conteúdo do capítulo.</p>',
            'contentformat' => FORMAT_HTML,
            'hidden' => 0,
            'timemodified' => time(),
            'importsrc' => '',
        ]);

        rebuild_course_cache($course->id, true);
        $course = get_course($course->id);
        $items = (new extractor($course))->extract_all();
        $types = array_column(array_map(static fn($item): array => ['type' => $item->type], $items), 'type');

        foreach (['section', 'page', 'assignment', 'forum', 'label', 'book'] as $type) {
            $this->assertContains($type, $types);
        }
    }

    /**
     * Invalid opaque keys are rejected before any record lookup.
     */
    public function test_rejects_invalid_key(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $this->expectException(moodle_exception::class);
        (new extractor($course))->extract_by_key('../../user:1');
    }
}
