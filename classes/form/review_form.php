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
 * Review selection form.
 *
 * @package   local_plainlanguage
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_plainlanguage\form;

use moodleform;

defined('MOODLE_INTERNAL') || die;
global $CFG;

require_once($CFG->libdir . '/formslib.php');

/**
 * Select one item or the whole course.
 */
class review_form extends moodleform {
    /** Define form controls. */
    protected function definition(): void {
        $mform = $this->_form;
        $courseid = (int)$this->_customdata['courseid'];
        $items = $this->_customdata['items'];

        $mform->addElement('hidden', 'courseid', $courseid);
        $mform->setType('courseid', PARAM_INT);

        $mform->addElement('select', 'scope', get_string('scope', 'local_plainlanguage'), [
            'item' => get_string('scopeitem', 'local_plainlanguage'),
            'course' => get_string('scopecourse', 'local_plainlanguage'),
        ]);
        $mform->setType('scope', PARAM_ALPHA);

        $mform->addElement('select', 'itemkey', get_string('contentitem', 'local_plainlanguage'),
            ['' => get_string('selectcontent', 'local_plainlanguage')] + $items);
        $mform->setType('itemkey', PARAM_RAW_TRIMMED);

        $mform->addElement('advcheckbox', 'ignorecache', get_string('ignorecache', 'local_plainlanguage'));
        $mform->setType('ignorecache', PARAM_BOOL);

        $this->add_action_buttons(false, get_string('review', 'local_plainlanguage'));
    }

    /**
     * Validate item selection.
     *
     * @param array $data Submitted data.
     * @param array $files Submitted files.
     * @return array
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);
        if (($data['scope'] ?? '') === 'item' && trim((string)($data['itemkey'] ?? '')) === '') {
            $errors['itemkey'] = get_string('validationselectitem', 'local_plainlanguage');
        }
        return $errors;
    }
}
