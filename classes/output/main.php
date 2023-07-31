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
 * Class containing renderers for the block.
 *
 * @package   block_progressmeter
 * @copyright 2023 David Herney @ BambuCo
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_progressmeter\output;

use renderable;
use renderer_base;
use templatable;

/**
 * Class containing data for the block.
 *
 * @copyright 2023 David Herney @ BambuCo
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class main implements renderable, templatable {

    /**
     * @var array List of measures to print.
     */
    private $measures;

    /**
     * @var object Info about courses view.
     */
    private $coursesview;

    /**
     * Constructor.
     *
     * @param array $measures The measures configuration.
     * @param object|null $coursesview The courses list.
     */
    public function __construct(array $measures = [], $coursesview = null) {
        global $CFG, $OUTPUT;

        $this->measures = $measures;
        $this->coursesview = $coursesview;
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param \renderer_base $output
     * @return array Context variables for the template
     */
    public function export_for_template(renderer_base $output) {
        global $CFG, $PAGE, $OUTPUT;

        $errormessage = '';
        $style = get_config('block_progressmeter', 'style');
        $showmeasures = [];
        $stylebytype = [
            'withcompletion' => 'primary',
            'completed' => 'success',
            'inprogress' => 'warning'
        ];

        foreach ($this->measures as $measure) {
            $measure->hassubtitle = !empty($measure->subtitle) && count($this->measures) > 1;

            foreach ($measure->data as $data) {
                if (!empty($data->type)) {

                    if (!empty($stylebytype[$data->type])) {
                        $data->style = $stylebytype[$data->type];
                    }

                    if ($data->value > 0) {
                        $data->viewurl = new \moodle_url('/blocks/progressmeter/view.php', ['t' => $data->type,
                                                                                            'm' => $measure->type]);
                    }
                }
            }

            $showmeasures[] = $measure;
        }

        $defaultvariables = [
            'uniqueid' => uniqid(),
            'baseurl' => $CFG->wwwroot,
            'hasmeasures' => count($showmeasures) > 1,
            'measures' => $showmeasures,
            'style' => $style,
        ];

        if (is_object($this->coursesview)) {
            $courses = [];
            foreach ($this->coursesview->list as $course) {

                $course->viewurl = new \moodle_url('/course/view.php', ['id' => $course->id]);

                $course->courseimage = '';
                $coursefull = new \core_course_list_element($course);
                foreach ($coursefull->get_course_overviewfiles() as $file) {
                    $isimage = $file->is_valid_image();
                    $url = file_encode_url("$CFG->wwwroot/pluginfile.php",
                            '/'. $file->get_contextid(). '/'. $file->get_component(). '/'.
                            $file->get_filearea(). $file->get_filepath(). $file->get_filename(), !$isimage);
                    if ($isimage) {
                        $course->courseimage = $url;
                        break;
                    }
                }

                if (empty($course->courseimage)) {
                    $course->courseimage = $OUTPUT->get_generated_image_for_id($course->id);
                }

                if ($course->enablecompletion) {
                    $progress = \core_completion\progress::get_course_progress_percentage($course);
                    $course->progress = round($progress);
                    $course->hasprogress = !empty($progress) || $progress === 0;
                }

                $courses[] = $course;
            }

            $defaultvariables['fullview'] = true;
            $defaultvariables['hascourses'] = count($courses) > 0;
            $defaultvariables['courses'] = $courses;
            $defaultvariables['coursestype'] = $this->coursesview->type;
            $defaultvariables['subtitle'] = get_string('label_' . $this->coursesview->type, 'block_progressmeter');
            $defaultvariables['errormessage'] = count($courses) > 0 ? '' :
                                                    get_string('not_courses_' . $this->coursesview->type, 'block_progressmeter');

            $helpkey = $this->coursesview->mode . 'courses_' . $this->coursesview->type . '_help';
            $defaultvariables['courseshelpmessage'] = get_string($helpkey, 'block_progressmeter');

        }

        return $defaultvariables;
    }
}
