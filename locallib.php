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
 * Common functions.
 *
 * @package   block_progressmeter
 * @copyright 2020 David Herney Bernal - cirano
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

function block_progressmeter_gettypes() {
    return ['withcompletion', 'completed', 'pending'];
}

function block_progressmeter_getcourses($type, $mode) {
    global $CFG, $DB, $USER;

    $courses = [];
    $studentroles = $CFG->gradebookroles;

    // Only available if recognize roles to students.
    if (empty($studentroles)) {
        return $courses;
    }

    $fieldid = get_config('block_progressmeter', 'bossfield');

    if ($type == 'completed') {

        if ($mode == 'team' && $fieldid) {
            $sql = "SELECT DISTINCT c.*
                    FROM {course} AS c
                        INNER JOIN {course_completions} AS cc ON c.enablecompletion = 1
                                        AND cc.timecompleted IS NOT NULL AND cc.course = c.id
                        INNER JOIN {context} AS cx ON cx.instanceid = c.id AND cx.contextlevel = ?
                        INNER JOIN {role_assignments} AS ra ON ra.roleid IN ({$studentroles}) AND ra.contextid = cx.id
                        INNER JOIN {user_info_data} AS uid ON uid.fieldid = ? AND uid.data = ? AND uid.userid = ra.userid
                    WHERE c.visible = 1
                    ORDER BY c.sortorder ASC";
            $courses = $DB->get_records_sql($sql, [CONTEXT_COURSE, $fieldid, $USER->username]);
        } else {
            $sql = "SELECT c.*
                    FROM {course} AS c
                        INNER JOIN {course_completions} AS cc ON c.enablecompletion = 1 AND cc.userid = ?
                                        AND cc.timecompleted IS NOT NULL AND cc.course = c.id
                        INNER JOIN {context} AS cx ON cx.instanceid = c.id AND cx.contextlevel = ?
                        INNER JOIN {role_assignments} AS ra ON ra.roleid IN ({$studentroles}) AND
                                                                ra.contextid = cx.id AND ra.userid = ?
                    WHERE c.visible = 1
                    ORDER BY c.sortorder ASC";
            $courses = $DB->get_records_sql($sql, [$USER->id, CONTEXT_COURSE, $USER->id]);
        }

    } else if ($type == 'withcompletion') {
        if ($mode == 'team' && $fieldid) {
            $sql = "SELECT DISTINCT c.*
                    FROM {course} AS c
                        INNER JOIN {course_completions} AS cc ON c.enablecompletion = 1 AND cc.course = c.id
                        INNER JOIN {context} AS cx ON cx.instanceid = c.id AND cx.contextlevel = ?
                        INNER JOIN {role_assignments} AS ra ON ra.roleid IN ({$studentroles}) AND ra.contextid = cx.id
                        INNER JOIN {user_info_data} AS uid ON uid.fieldid = ? AND uid.data = ? AND uid.userid = ra.userid
                    WHERE c.visible = 1
                    ORDER BY c.sortorder ASC";
            $courses = $DB->get_records_sql($sql, [CONTEXT_COURSE, $fieldid, $USER->username]);
        } else {
            $sql = "SELECT c.*
                    FROM {course} AS c
                        INNER JOIN {course_completions} AS cc ON c.enablecompletion = 1 AND cc.userid = ? AND cc.course = c.id
                        INNER JOIN {context} AS cx ON cx.instanceid = c.id AND cx.contextlevel = ?
                        INNER JOIN {role_assignments} AS ra ON ra.roleid IN ({$studentroles}) AND
                                                                ra.contextid = cx.id AND ra.userid = ?
                    WHERE c.visible = 1
                    ORDER BY c.sortorder ASC";
            $courses = $DB->get_records_sql($sql, [$USER->id, CONTEXT_COURSE, $USER->id]);
        }

    } else if ($type == 'pending') {
        if ($mode == 'team' && $fieldid) {
            $sql = "SELECT DISTINCT c.*
                    FROM {course} AS c
                        INNER JOIN {course_completions} AS cc ON c.enablecompletion = 1 AND
                                                                    cc.course = c.id AND cc.timecompleted IS NULL
                        INNER JOIN {context} AS cx ON cx.instanceid = c.id AND cx.contextlevel = ?
                        INNER JOIN {role_assignments} AS ra ON ra.roleid IN ({$studentroles}) AND ra.contextid = cx.id
                        INNER JOIN {user_info_data} AS uid ON uid.fieldid = ? AND uid.data = ? AND uid.userid = ra.userid
                    WHERE c.visible = 1
                    ORDER BY c.sortorder ASC";
            $courses = $DB->get_records_sql($sql, [CONTEXT_COURSE, $fieldid, $USER->username]);
        } else {
            $sql = "SELECT DISTINCT c.*
                    FROM {course} AS c
                        INNER JOIN {course_completions} AS cc ON c.enablecompletion = 1 AND cc.userid = ? AND
                                                                    cc.course = c.id AND cc.timecompleted IS NULL
                        INNER JOIN {context} AS cx ON cx.instanceid = c.id AND cx.contextlevel = ?
                        INNER JOIN {role_assignments} AS ra ON ra.roleid IN ({$studentroles}) AND
                                                            ra.contextid = cx.id AND ra.userid = ?
                    WHERE c.visible = 1
                    ORDER BY c.sortorder ASC";
            $courses = $DB->get_records_sql($sql, [$USER->id, CONTEXT_COURSE, $USER->id, $USER->id]);
        }
    }

    $list = [];
    foreach($courses as $course) {

        if ($course->id == SITEID) {
            continue;
        }

        if (!$course->visible) {
            $context = context_course::instance($course->id, MUST_EXIST);

            if(!has_capability('moodle/course:viewhiddencourses', $context)) {
                continue;
            }
        }

        $list[] = $course;
    }

    return $list;
}

function block_progressmeter_loaddata() {
    global $CFG, $USER, $DB, $PAGE, $SESSION;

    if (!property_exists($SESSION, 'block_progressmeter_cache')) {
        $data = new stdClass();
        $data->time = 0;
        $data->measures = [];
        $SESSION->block_progressmeter_cache = $data;
    } else {
        $data = $SESSION->block_progressmeter_cache;
    }

    // Only available if recognize roles to students.
    $studentroles = $CFG->gradebookroles;
    if (empty($studentroles)) {
        return [];
    }

    $measures = [];

    $cachetime = get_config('block_progressmeter', 'cachetime');
    if ($data->time + $cachetime < time()) {

        $sql = "SELECT COUNT(DISTINCT c.id)
                FROM {course} AS c
                    INNER JOIN {course_completions} AS cc ON c.enablecompletion = 1 AND cc.userid = ? AND cc.course = c.id
                    INNER JOIN {context} AS cx ON cx.instanceid = c.id AND cx.contextlevel = ?
                    INNER JOIN {role_assignments} AS ra ON ra.roleid IN ({$studentroles}) AND
                                                            ra.contextid = cx.id AND ra.userid = ?
                WHERE c.visible = 1";
        $total = $DB->count_records_sql($sql, [$USER->id, CONTEXT_COURSE, $USER->id]);

        $completed = 0;
        if ($total > 0) {
            $sql = "SELECT COUNT(DISTINCT c.id)
                        FROM {course} AS c
                        INNER JOIN {course_completions} AS cc ON c.enablecompletion = 1 AND cc.userid = ?
                                        AND cc.timecompleted IS NOT NULL AND cc.course = c.id
                        INNER JOIN {context} AS cx ON cx.instanceid = c.id AND cx.contextlevel = ?
                        INNER JOIN {role_assignments} AS ra ON ra.roleid IN ({$studentroles}) AND
                                                                ra.contextid = cx.id AND ra.userid = ?
                WHERE c.visible = 1";
            $completed = $DB->count_records_sql($sql, [$USER->id, CONTEXT_COURSE, $USER->id]);

            // Fix for deleted courses and not clean completions.
            $completed = $completed > $total ? $total : $completed;
        }

        $measure = new \stdClass();
        $measure->type = 'user';
        $measure->subtitle = get_string('usermeter', 'block_progressmeter');
        $measure->percent = $total > 0 ? round($completed / $total * 100) : 0;

        $measure->data = [
            (object)[
                'type' => 'withcompletion',
                'value' => $total,
                'label' => get_string('label_withcompletion', 'block_progressmeter')
            ],
            (object)[
                'type' => 'completed',
                'value' => $completed,
                'label' => get_string('completed', 'block_progressmeter')
            ],
            (object)[
                'type' => 'pending',
                'value' => $total - $completed,
                'label' => get_string('pending', 'block_progressmeter')
            ]
        ];

        $measures[] = $measure;

        // Teams meter.
        $fieldid = get_config('block_progressmeter', 'bossfield');

        if ($fieldid) {

            $sql = 'SELECT uid.userid FROM {user_info_data} AS uid
                        INNER JOIN {user} AS u ON u.id = uid.userid AND u.deleted = 0 AND u.suspended = 0
                                            AND uid.' . $DB->sql_compare_text('data') . ' = ' . $DB->sql_compare_text(':userid')
                        . ' WHERE uid.fieldid = :fieldid';

            $params = ['fieldid' => $fieldid, 'userid' => $USER->username];
            $userslist = $DB->get_records_sql($sql, $params);

            if (count($userslist) > 0) {

                $usersids = [];
                foreach ($userslist as $one) {
                    $usersids[] = $one->userid;
                }

                $usersids = implode(',', $usersids);

                $sql = "SELECT COUNT(DISTINCT c.id, cc.userid)
                        FROM {course} AS c
                            INNER JOIN {course_completions} AS cc ON c.enablecompletion = 1 AND cc.userid IN ({$usersids})
                                                                    AND cc.course = c.id
                            INNER JOIN {context} AS cx ON cx.instanceid = c.id AND cx.contextlevel = ?
                            INNER JOIN {role_assignments} AS ra ON ra.contextid = cx.id AND ra.userid = cc.userid
                                                        AND ra.roleid IN ({$CFG->gradebookroles})
                        WHERE c.visible = 1";
                $total = $DB->count_records_sql($sql, [CONTEXT_COURSE]);

                if ($total > 0) {
                    $sql = "SELECT COUNT(DISTINCT c.id, cc.userid)
                            FROM {course} AS c
                                INNER JOIN {course_completions} AS cc ON c.enablecompletion = 1 AND cc.userid IN ({$usersids})
                                                AND cc.timecompleted IS NOT NULL AND cc.course = c.id
                                INNER JOIN {context} AS cx ON cx.instanceid = c.id AND cx.contextlevel = ?
                                INNER JOIN {role_assignments} AS ra ON ra.contextid = cx.id AND ra.userid = cc.userid
                                                            AND ra.roleid IN ({$CFG->gradebookroles})
                            WHERE c.visible = 1";
                    $completed = $DB->count_records_sql($sql, [CONTEXT_COURSE]);

                    $measure = new \stdClass();
                    $measure->type = 'team';
                    $measure->subtitle = get_string('teammeter', 'block_progressmeter');
                    $measure->percent = round($completed / $total * 100);
                    $measure->data = [
                        (object)[
                            'type' => 'withcompletion',
                            'value' => $total,
                            'label' => get_string('label_withcompletion', 'block_progressmeter')
                        ],
                        (object)[
                            'type' => 'completed',
                            'value' => $completed,
                            'label' => get_string('completed', 'block_progressmeter')
                        ],
                        (object)[
                            'type' => 'pending',
                            'value' => $total - $completed,
                            'label' => get_string('pending', 'block_progressmeter')
                        ]
                    ];

                    $bmanager = new \block_manager($PAGE);
                    if ($bmanager->is_known_block_type('rate_course')) {
                        $measure->moreurl = new moodle_url('/report/userapproval/index.php');
                    }

                    $measures[] = $measure;
                }
            }
        }

        $data->time = time();
        $data->measures = $measures;
        $SESSION->block_progressmeter_cache = $data;
    } else {
        $measures = $data->measures;
    }

    return $measures;
}
