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
 * Extract supported teacher-authored course content.
 *
 * @package   local_plainlanguage
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_plainlanguage\content;

use context_course;
use context_module;
use moodle_exception;
use stdClass;

/**
 * Extractor for supported course content.
 */
class extractor {
    /** @var string[] Supported activity module names. */
    private const SUPPORTED_MODULES = ['page', 'book', 'assign', 'forum', 'label'];

    /**
     * Constructor.
     *
     * @param stdClass $course Course record.
     */
    public function __construct(private readonly stdClass $course) {
    }

    /**
     * Extract every supported content item in the course.
     *
     * No user submissions, posts, grades or other student records are queried here.
     *
     * @return content_item[]
     */
    public function extract_all(): array {
        global $DB;

        $items = [];
        $coursecontext = context_course::instance((int)$this->course->id);

        $sections = $DB->get_records('course_sections', ['course' => $this->course->id], 'section ASC');
        foreach ($sections as $section) {
            if (trim(strip_tags((string)$section->summary)) === '') {
                continue;
            }
            $name = trim((string)$section->name);
            if ($name === '') {
                $name = get_section_name($this->course, $section);
            }
            $items[] = new content_item(
                'section:' . $section->id,
                'section',
                $name,
                (string)$section->summary,
                (int)$section->summaryformat,
                $coursecontext->id,
                (int)$this->course->id,
            );
        }

        $modinfo = get_fast_modinfo($this->course);
        foreach ($modinfo->get_cms() as $cm) {
            if (!in_array($cm->modname, self::SUPPORTED_MODULES, true) || $cm->deletioninprogress) {
                continue;
            }

            $context = context_module::instance($cm->id);
            switch ($cm->modname) {
                case 'page':
                    $record = $DB->get_record('page', ['id' => $cm->instance],
                        'id,name,content,contentformat', MUST_EXIST);
                    if ($this->has_text((string)$record->content)) {
                        $items[] = new content_item(
                            'page:' . $cm->id,
                            'page',
                            (string)$record->name,
                            (string)$record->content,
                            (int)$record->contentformat,
                            $context->id,
                            (int)$this->course->id,
                        );
                    }
                    break;

                case 'book':
                    $book = $DB->get_record('book', ['id' => $cm->instance], 'id,name', MUST_EXIST);
                    $chapters = $DB->get_records('book_chapters', ['bookid' => $book->id], 'pagenum ASC, id ASC');
                    foreach ($chapters as $chapter) {
                        if (!$this->has_text((string)$chapter->content)) {
                            continue;
                        }
                        $items[] = new content_item(
                            'bookchapter:' . $cm->id . ':' . $chapter->id,
                            'book',
                            (string)$book->name . ' — ' . (string)$chapter->title,
                            (string)$chapter->content,
                            (int)$chapter->contentformat,
                            $context->id,
                            (int)$this->course->id,
                        );
                    }
                    break;

                case 'assign':
                    $record = $DB->get_record('assign', ['id' => $cm->instance],
                        'id,name,intro,introformat', MUST_EXIST);
                    if ($this->has_text((string)$record->intro)) {
                        $items[] = new content_item(
                            'assign:' . $cm->id,
                            'assignment',
                            (string)$record->name,
                            (string)$record->intro,
                            (int)$record->introformat,
                            $context->id,
                            (int)$this->course->id,
                        );
                    }
                    break;

                case 'forum':
                    $record = $DB->get_record('forum', ['id' => $cm->instance],
                        'id,name,intro,introformat', MUST_EXIST);
                    if ($this->has_text((string)$record->intro)) {
                        $items[] = new content_item(
                            'forum:' . $cm->id,
                            'forum',
                            (string)$record->name,
                            (string)$record->intro,
                            (int)$record->introformat,
                            $context->id,
                            (int)$this->course->id,
                        );
                    }
                    break;

                case 'label':
                    $record = $DB->get_record('label', ['id' => $cm->instance],
                        'id,name,intro,introformat', MUST_EXIST);
                    if ($this->has_text((string)$record->intro)) {
                        $title = trim((string)$record->name);
                        if ($title === '') {
                            $title = get_string('sourcelabel', 'local_plainlanguage');
                        }
                        $items[] = new content_item(
                            'label:' . $cm->id,
                            'label',
                            $title,
                            (string)$record->intro,
                            (int)$record->introformat,
                            $context->id,
                            (int)$this->course->id,
                        );
                    }
                    break;
            }
        }

        return $items;
    }

    /**
     * Find one item by the opaque key emitted by extract_all().
     *
     * @param string $key Item key.
     * @return content_item
     */
    public function extract_by_key(string $key): content_item {
        if (!preg_match('/^(section|page|assign|forum|label):\d+$|^bookchapter:\d+:\d+$/', $key)) {
            throw new moodle_exception('invalidcontentkey', 'local_plainlanguage');
        }

        foreach ($this->extract_all() as $item) {
            if (hash_equals($item->key, $key)) {
                return $item;
            }
        }

        throw new moodle_exception('contentnotfound', 'local_plainlanguage');
    }

    /**
     * Test whether a field contains meaningful text.
     *
     * @param string $html HTML/text.
     * @return bool
     */
    private function has_text(string $html): bool {
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return trim(preg_replace('/\s+/u', ' ', $text) ?? '') !== '';
    }
}
