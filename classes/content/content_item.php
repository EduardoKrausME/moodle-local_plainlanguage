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
        public readonly string $key,
        public readonly string $type,
        public readonly string $title,
        public readonly string $html,
        public readonly int    $format,
        public readonly int    $contextid,
        public readonly int    $courseid,
    ) {
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
