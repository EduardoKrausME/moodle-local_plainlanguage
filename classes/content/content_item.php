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
 * A teacher-authored content item eligible for review.
 *
 * @package   local_plainlanguage
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_plainlanguage\content;

use context;

/**
 * Immutable content item.
 */
class content_item {
    /** @var string Stable key used only inside this plugin. */
    public readonly string $key;
    /** @var string Supported source type. */
    public readonly string $type;
    /** @var string Human-readable title. */
    public readonly string $title;
    /** @var string Teacher-authored source HTML/text. */
    public readonly string $html;
    /** @var int Moodle text format. */
    public readonly int $format;
    /** @var int Context used to format the content. */
    public readonly int $contextid;
    /** @var int Course id. */
    public readonly int $courseid;

    /**
     * Constructor.
     *
     * @param string $key Stable key used only inside this plugin.
     * @param string $type Supported source type.
     * @param string $title Human-readable title.
     * @param string $html Teacher-authored source HTML/text.
     * @param int $format Moodle text format.
     * @param int $contextid Context used to format the content.
     * @param int $courseid Course id.
     */
    public function __construct(
        string $key,
        string $type,
        string $title,
        string $html,
        int $format,
        int $contextid,
        int $courseid
    ) {
        $this->key = $key;
        $this->type = $type;
        $this->title = $title;
        $this->html = $html;
        $this->format = $format;
        $this->contextid = $contextid;
        $this->courseid = $courseid;
    }

    /**
     * Return cleaned formatted HTML for display only.
     *
     * @return string
     */
    public function formatted_html(): string {
        $context = context::instance_by_id($this->contextid);
        return format_text($this->html, $this->format, [
            'context' => $context,
            'para' => false,
            'filter' => true,
            'noclean' => false,
        ]);
    }
}
