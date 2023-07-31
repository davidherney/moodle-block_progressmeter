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
 * Block renderer
 *
 * @package   block_progressmeter
 * @copyright 2023 David Herney @ BambuCo
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_progressmeter\output;

use plugin_renderer_base;
use renderable;

/**
 * Vitrina block renderer
 *
 * @copyright 2023 David Herney @ BambuCo
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends plugin_renderer_base {

    /**
     * Return the template content for the block.
     *
     * @param main $main The main renderable
     * @return string HTML string
     */
    public function render_main(main $main) : string {
        global $CFG, $PAGE;

        $style = get_config('block_progressmeter', 'style');
        $path = $CFG->dirroot . '/blocks/progressmeter/templates/' . $style . '/main.mustache';

        if ($style != 'default' && file_exists($path)) {
            $templatefile = 'block_progressmeter/' . $style . '/main';
        } else {
            $templatefile = 'block_progressmeter/main';
        }

        $style = get_config('block_progressmeter', 'style');
        $csspath = $CFG->dirroot . '/blocks/progressmeter/templates/' . $style . '/styles.css';

        // If the template is not the default and a specific CSS file exist, include the CSS file.
        if ($style != 'default' && file_exists($csspath)) {
            $PAGE->requires->css('/blocks/progressmeter/templates/' . $style . '/styles.css');
        }

        $PAGE->requires->js_call_amd('block_progressmeter/main', 'init');

        return $this->render_from_template($templatefile, $main->export_for_template($this));
    }

}
