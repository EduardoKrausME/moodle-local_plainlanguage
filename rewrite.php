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
 * Generate a non-persistent rewrite suggestion.
 *
 * @package   local_plainlanguage
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

use local_plainlanguage\content\extractor;
use local_plainlanguage\exception\ai_exception;
use local_plainlanguage\service\review_service;

$courseid = required_param('courseid', PARAM_INT);
$itemkey = required_param('itemkey', PARAM_RAW_TRIMMED);
$ignorecache = optional_param('ignorecache', 0, PARAM_BOOL);

require_sesskey();
$course = get_course($courseid);
require_login($course);

$context = context_course::instance($courseid);
require_capability('local/plainlanguage:review', $context);

$PAGE->set_url(new moodle_url('/local/plainlanguage/rewrite.php'));
$PAGE->set_context($context);
$PAGE->set_course($course);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(get_string('rewriteheading', 'local_plainlanguage'));
$PAGE->set_heading(format_string($course->fullname));

$extractor = new extractor($course);
$item = $extractor->extract_by_key($itemkey);
$service = new review_service();

$rewrite = null;
$error = '';
try {
    $rewrite = $service->rewrite($item, (bool)$ignorecache);
} catch (ai_exception $e) {
    $error = $e->getMessage();
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('rewriteheading', 'local_plainlanguage'));

if ($error !== '') {
    echo $OUTPUT->notification($error, 'error');
} else if ($rewrite !== null) {
    echo $OUTPUT->render_from_template('local_plainlanguage/rewrite', $rewrite);
}

$backurl = new moodle_url('/local/plainlanguage/index.php', ['courseid' => $courseid]);
echo $OUTPUT->single_button($backurl, get_string('backtoreview', 'local_plainlanguage'), 'get');
echo $OUTPUT->footer();
