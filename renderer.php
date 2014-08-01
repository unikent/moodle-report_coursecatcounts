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
 * @copyright  2014 University of Kent
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Implements the plugin renderer
 *
 * @copyright  2014 University of Kent
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_coursecatcounts_renderer extends plugin_renderer_base {
    /**
     * This function will render a table.
     *
     * @return string HTML to output.
     */
    public function run_report($startdate, $enddate) {
        global $DB;

        $table = new html_table();
        $table->head  = array(
            'Category',
            'Total From Course',
            'Ceased',
            'Total',
            'Active',
            'Resting',
            'Inactive',
            'Per C Active',
            'Guest',
            'Keyed',
            'Per C Guest'
        );
        $table->attributes['class'] = 'admintable generaltable';
        $table->data = array();

        $data = $this->get_data($startdate, $enddate);
        foreach ($data as $row) {
            $table->data[] = new html_table_row(array(
                new html_table_cell($row->category),
                new html_table_cell($row->total_from_course),
                new html_table_cell($row->ceased),
                new html_table_cell($row->total),
                new html_table_cell($row->active),
                new html_table_cell($row->resting),
                new html_table_cell($row->inactive),
                new html_table_cell($row->per_c_active),
                new html_table_cell($row->guest),
                new html_table_cell($row->keyed),
                new html_table_cell($row->per_c_guest)
            ));
        }

        return html_writer::table($table);
    }

    /**
     * Returns data for the table.
     */
    public function get_data($startdate, $enddate) {
        // This is the SQL this report needs to replace.
        $sql = <<<SQL
        SELECT
        cco.path,
        cco.name,
        COUNT(c.id) total_from_course,

        SUM(
            CASE WHEN (stud.cnt < 2 OR stud.cnt IS NULL)
                THEN 1
                ELSE 0
            END
        ) Ceased,

        COUNT(c.id) - SUM(
            CASE WHEN (stud.cnt < 2 OR stud.cnt IS NULL)
                THEN 1
                ELSE 0
            END
        ) Total,

        SUM(
            CASE WHEN (stud.cnt > 1 AND stud.cnt IS NOT NULL)
            AND mods.cnt > 0
            AND mods.cnt2 > 0
            AND c.visible=1
                THEN 1
                ELSE 0
            END
        ) Active,

        SUM(
            CASE WHEN (stud.cnt > 1 AND stud.cnt IS NOT NULL)
            AND mods.cnt > 0 AND mods.cnt2 > 0 AND c.visible=0
                THEN 1
                ELSE 0
            END
        ) Resting,

        SUM(
            CASE WHEN (stud.cnt > 1 AND stud.cnt IS NOT NULL)
            AND (mods.cnt < 1 OR mods.cnt IS NULL)
            AND (mods.cnt2 < 1 OR mods.cnt2 IS NULL)
                THEN 1
                ELSE 0
            END
        ) Inactive,

        SUM(
            CASE WHEN (stud.cnt > 1 AND stud.cnt IS NOT NULL)
            AND mods.cnt > 0
            AND mods.cnt2 > 0
            AND c.visible=1
                THEN 1
                ELSE 0
            END
        ) * 100 / (
            COUNT(c.id) - SUM(
                CASE WHEN (stud.cnt < 2 OR stud.cnt IS NULL)
                    THEN 1
                    ELSE 0
                END
            )
        ) per_c_active,

        SUM(
            CASE WHEN en.statcnt>0
                THEN 1
                ELSE 0
            END
        ) Guest,

        SUM(
            CASE WHEN en.keycnt>0
                THEN 1
                ELSE 0
            END
        ) Keyed,

        SUM(
            CASE WHEN en.statcnt>0
                THEN 1
                ELSE 0
            END
        ) * 100 / SUM(
            CASE WHEN (stud.cnt > 1 AND stud.cnt IS NOT NULL) AND mods.cnt > 0 AND mods.cnt2 > 0 AND c.visible = 1
                THEN 1
                ELSE 0
            END
        ) per_c_guest

        FROM {course} c

        RIGHT OUTER JOIN {course_categories} cc
            ON c.category = cc.id

        RIGHT OUTER JOIN {course_categories} cco
            ON CONCAT(cc.path,'/') LIKE CONCAT(cco.path, '/%')

        LEFT OUTER JOIN (
            SELECT e.courseid, COUNT(*) cnt
                FROM {user_enrolments} ue
            JOIN {enrol} e
                ON ue.enrolid = e.id
            JOIN {role} r
                ON e.roleid = r.id
            WHERE r.shortname in ('student', 'sds_student')
            GROUP BY courseid
        ) stud
            ON stud.courseid = c.id

        LEFT OUTER JOIN (
            SELECT cm.course courseid, COUNT(*) cnt, COUNT(DISTINCT cm.module) cnt2
                FROM {course_modules} cm
            LEFT OUTER JOIN {course} c
                ON (c.timecreated BETWEEN cm.added - 120 and cm.added + 120)
                AND c.id = cm.course
            WHERE c.id IS NULL
            GROUP BY cm.course
        ) mods
            ON mods.courseid = c.id

        LEFT OUTER JOIN (
            SELECT
                e.courseid,
                COUNT(*) cnt,
                SUM (
                    CASE e.status WHEN 1
                        THEN 1
                        ELSE 0
                    END
                ) statcnt,
                SUM (
                    CASE WHEN e.password <> ''
                        THEN 1
                        ELSE 0
                    END
                ) keycnt
            FROM {enrol} e
                WHERE enrol = 'guest'
            GROUP BY e.courseid
        ) en
            ON en.courseid = c.id

        WHERE c.startdate BETWEEN :startdate and :enddate

        GROUP BY cco.path
SQL;
        return array();
    }
}
