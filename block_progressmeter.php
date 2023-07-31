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
 * Progressmeter block.
 *
 * @package   block_progressmeter
 * @copyright 2017 David Herney Bernal - cirano
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot . '/blocks/progressmeter/locallib.php');

class block_progressmeter extends block_base {
    function init() {
        $this->title = get_string('pluginname', 'block_progressmeter');
    }

    function applicable_formats() {
        return array('all' => true);
    }

    function has_config() {
        return true;
    }

    /**
     * All multiple instances of this block
     * @return bool Returns false
     */
    function instance_allow_multiple() {
        return false;
    }

    function get_content() {
        if ($this->content !== NULL) {
            return $this->content;
        }
        $this->content = (object)['text' => '', 'footer' => ''];

        // It is not available to guests.
        if (!isloggedin() || isguestuser()) {
            return $this->content;
        }

        $measures = block_progressmeter_loaddata();

        if (count($measures) == 0) {
            $this->content->text = get_string('not_advance', 'block_progressmeter');
        } else {

            // Load templates.
            $renderable = new \block_progressmeter\output\main($measures);
            $renderer = $this->page->get_renderer('block_progressmeter');
            $this->content->text = $renderer->render($renderable);
        }

        return $this->content;
    }

}
