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
 * Course plain-language review page.
 *
 * @package   local_plainlanguage
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

use local_plainlanguage\content\extractor;
use local_plainlanguage\form\review_form;
use local_plainlanguage\service\review_service;

$courseid = required_param('courseid', PARAM_INT);
$course = get_course($courseid);
require_login($course);

$context = context_course::instance($courseid);
require_capability('local/plainlanguage:review', $context);

$PAGE->set_url(new moodle_url('/local/plainlanguage/index.php', ['courseid' => $courseid]));
$PAGE->set_context($context);
$PAGE->set_course($course);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(get_string('heading', 'local_plainlanguage'));
$PAGE->set_heading(format_string($course->fullname));

$extractor = new extractor($course);
$items = $extractor->extract_all();
$itemoptions = [];
foreach ($items as $item) {
    $itemoptions[$item->key] = get_string('source' . $item->type, 'local_plainlanguage') . ': ' . $item->title;
}

$mform = new review_form(null, [
    'courseid' => $courseid,
    'items' => $itemoptions,
]);

$results = [];
if ($data = $mform->get_data()) {
    $selected = [];
    if ($data->scope === 'course') {
        $selected = $items;
    } else {
        $selected[] = $extractor->extract_by_key((string)$data->itemkey);
    }

    $service = new review_service();
    foreach ($selected as $item) {
        $result = $service->review($item, !empty($data->ignorecache));
        $result['courseid'] = $courseid;
        $result['sesskey'] = sesskey();
        $result['rewriteurl'] = (new moodle_url('/local/plainlanguage/rewrite.php'))->out(false);
        $results[] = $result;
    }
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('heading', 'local_plainlanguage'));
echo html_writer::tag('p', get_string('intro', 'local_plainlanguage'));

if (!$items) {
    echo $OUTPUT->notification(get_string('nocontent', 'local_plainlanguage'), 'info');
} else {
    $mform->display();
}

if ($results) {
    echo $OUTPUT->heading(get_string('reviewresults', 'local_plainlanguage'), 3);
    echo $OUTPUT->render_from_template('local_plainlanguage/results', ['results' => $results]);
}

echo $OUTPUT->footer();
