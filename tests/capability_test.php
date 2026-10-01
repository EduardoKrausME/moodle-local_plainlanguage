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
 * Capability tests.
 *
 * @package   local_plainlanguage
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_plainlanguage;

use advanced_testcase;

/**
 * Validate plugin capability registration.
 */
final class capability_test extends advanced_testcase {
    /**
    * Capability is course-scoped and read-only in intent.
    */
    public function test_review_capability_definition(): void {
        $capability = get_capability_info('local/plainlanguage:review');
        $this->assertNotFalse($capability);
        $this->assertSame(CONTEXT_COURSE, (int)$capability->contextlevel);
        $this->assertSame('read', $capability->captype);
    }
}
