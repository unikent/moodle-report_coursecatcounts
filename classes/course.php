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
 */
class course
{
    use \local_kent\traits\datapod;

    const STATUS_ACTIVE = 0;
    const STATUS_RESTING = 1;
    const STATUS_EMPTY = 2;
    const STATUS_UNUSED = 4;

    /**
     * Constructor.
     */
    public function __construct($data) {
        $this->set_data($data);
    }

    /**
     * Returns fast info for a course.
     */
    private function get_fast_info() {
        global $DB;

        $cache = \cache::make('report_coursecatcounts', 'coursefastinfo');
        if ($content = $cache->get($this->id)) {
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

        foreach ($DB->get_records_sql($sql) as $data) {
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

        foreach ($DB->get_records_sql($sql) as $data) {
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

        foreach ($DB->get_records_sql($sql) as $data) {
            $content[$data->courseid]->sections = $data->cnt;
            $content[$data->courseid]->section_length = $data->len;
        }

        // Build enrol/guest info.
        $sql = <<<SQL
            SELECT
                e.courseid,
                SUM(
                    CASE e.status WHEN 1
                        THEN 1
                        ELSE 0
                    END
                ) statcnt,
                SUM(
                    CASE WHEN e.password <> ''
                        THEN 1
                        ELSE 0
                    END
                ) keycnt
            FROM {enrol} e
                WHERE enrol = 'guest'
            GROUP BY e.courseid
SQL;

        foreach ($DB->get_records_sql($sql) as $data) {
            $content[$data->courseid]->guest_enabled = $data->statcnt > 0;
            $content[$data->courseid]->guest_password = $data->keycnt > 0;
        }

        foreach ($content as $id => $data) {
            $cache->set($id, $data);
        }

        return $content[$this->id];
    }

    /**
     * Returns the course's state:
     *  - Active: Has students/content and is visible.
     *  - Resting: Has students/content but is not visible.
     *  - Empty: Has students but no content.
     *  - Unused: Has no students.
     */
    public function get_state($astext = false) {
        $info = $this->get_fast_info();

        $state = self::STATUS_ACTIVE;
        if ($info->enrolments == 0) {
            $state = self::STATUS_UNUSED;
        } else if ($info->modules == 0 && $info->section_length == 0) {
            $state = self::STATUS_EMPTY;
        } else if (!$this->visible) {
            $state = self::STATUS_RESTING;
        }

        if (!$astext) {
            return $state;
        }

        switch ($state) {
            case self::STATUS_ACTIVE:
                $state = 'active';
            break;

            case self::STATUS_RESTING:
                $state = 'resting';
            break;

            case self::STATUS_EMPTY:
                $state = 'empty';
            break;

            case self::STATUS_UNUSED:
                $state = 'unused';
            break;
        }

        return $state;
    }

    /**
     * Return student count.
     */
    public function get_student_count() {
        $info = $this->get_fast_info();
        return $info->enrolments;
    }

    /**
     * Return activity count.
     */
    public function get_activity_count() {
        $info = $this->get_fast_info();
        return $info->modules;
    }

    /**
     * Return distinct activity count.
     */
    public function get_distinct_activity_count() {
        $info = $this->get_fast_info();
        return $info->distinct_modules;
    }

    /**
     * Does this course have guest access enabled?
     */
    public function is_guest_enabled() {
        $info = $this->get_fast_info();
        return isset($info->guest_enabled) ? (bool)$info->guest_enabled : false;
    }

    /**
     * Does this course have guest access passworded?
     */
    public function has_guest_password() {
        $info = $this->get_fast_info();
        return isset($info->guest_password) ? (bool)$info->guest_password : false;
    }
}
