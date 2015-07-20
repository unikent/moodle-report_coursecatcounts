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
 * Category-Based Course Counts
 *
 * @package    report_coursecatcounts
 * @copyright  2014 Skylar Kelty <S.Kelty@kent.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_coursecatcounts;

defined('MOODLE_INTERNAL') || die();

/**
 * Report course.
 * *** Beta API ***
 */
class course
{
    const STATUS_ACTIVE = 0;
    const STATUS_RESTING = 1;
    const STATUS_EMPTY = 2;
    const STATUS_UNUSED = 4;

    private $_data;

    /**
     * Constructor.
     */
    public function __construct($data) {
        $this->_data = $data;
    }

    /**
     * Returns fast info for a course.
     */
    private function get_fast_info() {
        global $DB;

        $cachekey = 'coursefastinfo';
        $cache = \cache::make('report_coursecatcounts', $cachekey);
        if ($content = $cache->get($cachekey)) {
            return $content;
        }

        $content = array();

        // Build enrolments.
        $sql = <<<SQL
            SELECT c.id as courseid, COALESCE(COUNT(ra.id), 0) cnt
            FROM {course} c
            INNER JOIN {context} ctx
                    ON ctx.instanceid = c.id
                    AND ctx.contextlevel = 50
            LEFT OUTER JOIN {role_assignments} ra
                    ON ra.contextid = ctx.id
            LEFT OUTER JOIN {role} r
                    ON ra.roleid = r.id AND r.shortname IN ('student', 'sds_student')
            GROUP BY c.id
SQL;

        foreach ($DB->get_records($sql) as $data) {
            $course = new \stdClass();
            $course->enrolments = $data->cnt;
            $content[$data->courseid] = $course;
        }

        // Build course modules.
        $sql = <<<SQL
            SELECT c.id as courseid, COALESCE(COUNT(cm.id), 0) cnt, COALESCE(COUNT(DISTINCT cm.module), 0) cnt2
            FROM {course} c
            LEFT OUTER JOIN {course_modules} cm
                ON c.id = cm.course
            GROUP BY c.id
SQL;

        foreach ($DB->get_records($sql) as $data) {
            $content[$data->courseid]->modules = $data->cnt;
            $content[$data->courseid]->distinct_modules = $data->cnt2;
        }

        // Build section info.
        $sql = <<<SQL
            SELECT c.id as courseid, COALESCE(COUNT(cs.id), 0) as cnt, LENGTH(GROUP_CONCAT(cs.summary)) as len
            FROM {course} c
            LEFT OUTER JOIN {course_sections} cs
                ON cs.course = c.id
            GROUP BY c.id
SQL;

        foreach ($DB->get_records($sql) as $data) {
            $content[$data->courseid]->sections = $data->cnt;
            $content[$data->courseid]->section_length = $data->len;
        }

        $cache->set($cachekey, $content);

        return $content;
    }

    /**
     * Returns the course's state:
     *  - Active: Has students/content and is visible.
     *  - Resting: Has students/content but is not visible.
     *  - Empty: Has students but no content.
     *  - Unused: Has no students.
     */
    public function get_state() {
        $info = $this->get_fast_info();
        $info = $info[$this->_data->id];

        if ($info->enrolments == 0) {
            return static::STATUS_UNUSED;
        }

        if ($info->modules == 0 && $info->section_length == 0) {
            return static::STATUS_EMPTY;
        }

        if (!$this->_data->visible) {
            return static::STATUS_RESTING;
        }

        return static::STATUS_ACTIVE;
    }
}
