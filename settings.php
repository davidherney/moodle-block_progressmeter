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
 * Settings for the block
 *
 * @package   block_progressmeter
 * @copyright 2023 David Herney Bernal - cirano
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {

    // Only support specific fields.
    $customfieldsupportedtypes = ['select', 'text'];

    // Get user fields.
    $userfields = [0 => get_string('notuseboss', 'block_progressmeter')];
    $customuserfields = $DB->get_records('user_info_field');

    foreach ($customuserfields as $k => $v) {
        if (in_array($v->datatype, $customfieldsupportedtypes)) {
            $userfields[$k] = format_string($v->name, true);
        }
    }

    $settings->add(new admin_setting_configselect('block_progressmeter/bossfield',
                                                    get_string('bossfield', 'block_progressmeter'),
                                                    get_string('bossfield_help', 'block_progressmeter'), '', $userfields));

    // Block style setting.
    $options = [
        'default' => get_string('style_default', 'block_progressmeter'),
        'circle' => get_string('style_circle', 'block_progressmeter'),
        'odometer' => get_string('style_odometer', 'block_progressmeter'),
    ];
    $settings->add(new admin_setting_configselect('block_progressmeter/style',
                                                    get_string('style', 'block_progressmeter'),
                                                    get_string('style_help', 'block_progressmeter'), '', $options));

    // Cache time setting.
    $settings->add(new admin_setting_configtext('block_progressmeter/cachetime',
                                                    get_string('cachetime', 'block_progressmeter'),
                                                    get_string('cachetime_help', 'block_progressmeter'), 120, PARAM_INT, 5));


}
