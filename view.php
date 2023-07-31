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
 * Display the course list
 *
 * @package   block_progressmeter
 * @copyright 2020 David Herney Bernal - cirano
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL
 */

require_once '../../config.php';
require_once $CFG->libdir.'/adminlib.php';
require_once 'locallib.php';

$type = optional_param('t', 'withcompletion', PARAM_ALPHA);
$mode = optional_param('m', 'u', PARAM_ALPHA);

$systemcontext = context_system::instance();

require_login(null, false);

// This page is not available to guests.
if (isguestuser()) {
    $url = new moodle_url($CFG->wwwroot);
    redirect($url);
    die();
}

// Only the available types.
$types = block_progressmeter_gettypes();

if (!in_array($type, $types)) {
    $type = 'withcompletion';
}

$PAGE->set_url('/blocks/progressmeter/view.php');
$PAGE->set_context($systemcontext);

$stitle = get_string('label_' . $type, 'block_progressmeter');
$PAGE->set_title($stitle);
$PAGE->set_heading(get_string('pluginname', 'block_progressmeter'));
$PAGE->set_pagelayout('mycourses');
$PAGE->add_body_class('limitedwidth');

$coursesview = new stdClass();
$coursesview->type = $type;
$coursesview->mode = $mode;
$coursesview->list = block_progressmeter_getcourses($type, $mode);
$measures = block_progressmeter_loaddata();

// Load templates to display courses.
$renderable = new \block_progressmeter\output\main($measures, $coursesview);
$renderer = $PAGE->get_renderer('block_progressmeter');
$content = $renderer->render($renderable);

echo $OUTPUT->header();

echo $content;

echo $OUTPUT->footer();
