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

class activity_report
{
    private $_activity;
    private $_startdate;
    private $_enddate;
    private $_activity_name;

    /**
     * Constructor.
     */
    public function __construct($activity, $startdate, $enddate) {
        global $DB;

        $this->_activity = $activity;
        $this->_startdate = $startdate;
        $this->_enddate = $enddate;

        $this->_activity_name = $DB->get_field('modules', 'name', array(
            'id' => $this->_activity
        ));
    }

    /**
     * Returns module name.
     */
    public function get_module_name() {
        return $this->_activity_name;
    }

    /**
     * Returns data.
     */
    public function get_data() {
        global $DB;

        $sql = <<<SQL
            SELECT
                cco.id as categoryid,
                cco.path,
                cco.name,

                /* Total Modules */
                COUNT(c.id) total,

                /* Total Modules with activity */
                SUM(
                    CASE WHEN (namedmods.moduleid = :activity1)
                        THEN 1
                        ELSE 0
                    END
                ) total_activity_count,

                /* Ceased Modules */
                SUM(
                    CASE WHEN (stud.cnt < 2 OR stud.cnt IS NULL)
                        THEN 1
                        ELSE 0
                    END
                ) ceased,

                /* Ceased Modules with activity */
                SUM(
                    CASE WHEN (stud.cnt < 2 OR stud.cnt IS NULL)
                    AND (namedmods.moduleid = :activity2)
                        THEN 1
                        ELSE 0
                    END
                ) ceased_activity_count,

                /* Active Modules */
                SUM(
                    CASE WHEN (stud.cnt > 1 AND stud.cnt IS NOT NULL)
                    AND mods.cnt > 0
                    AND mods.cnt2 > 0
                    AND c.visible = 1
                        THEN 1
                        ELSE 0
                    END
                ) active,

                /* Active Modules with activity */
                SUM(
                    CASE WHEN (stud.cnt > 1 AND stud.cnt IS NOT NULL)
                    AND mods.cnt > 0
                    AND mods.cnt2 > 0
                    AND c.visible = 1
                    AND namedmods.moduleid = :activity3
                        THEN 1
                        ELSE 0
                    END
                ) active_activity_count,

                /* Resting Modules */
                SUM(
                    CASE WHEN (stud.cnt > 1 AND stud.cnt IS NOT NULL)
                    AND mods.cnt > 0
                    AND mods.cnt2 > 0
                    AND c.visible = 0
                        THEN 1
                        ELSE 0
                    END
                ) resting,

                /* Resting Modules with activity */
                SUM(
                    CASE WHEN (stud.cnt > 1 AND stud.cnt IS NOT NULL)
                    AND mods.cnt > 0
                    AND mods.cnt2 > 0
                    AND c.visible = 0
                    AND namedmods.moduleid = :activity4
                        THEN 1
                        ELSE 0
                    END
                ) resting_activity_count,

                /* Inactive Modules */
                SUM(
                    CASE WHEN (stud.cnt > 1 AND stud.cnt IS NOT NULL)
                    AND (mods.cnt < 1 OR mods.cnt IS NULL)
                    AND (mods.cnt2 < 1 OR mods.cnt2 IS NULL)
                        THEN 1
                        ELSE 0
                    END
                ) inactive,

                /* Inactive Modules with activity */
                SUM(
                    CASE WHEN (stud.cnt > 1 AND stud.cnt IS NOT NULL)
                    AND (mods.cnt < 1 OR mods.cnt IS NULL)
                    AND (mods.cnt2 < 1 OR mods.cnt2 IS NULL)
                    AND namedmods.moduleid = :activity5
                        THEN 1
                        ELSE 0
                    END
                ) inactive_activity_count

            FROM {course} c

            RIGHT OUTER JOIN {course_categories} cc
                ON c.category = cc.id

            RIGHT OUTER JOIN {course_categories} cco
                ON CONCAT(cc.path,'/') LIKE CONCAT(cco.path, '/%')

            LEFT OUTER JOIN (
                SELECT c.id as courseid, COUNT(ra.id) cnt
                FROM {course} c
                INNER JOIN {context} ctx
                        ON ctx.instanceid=c.id
                        AND ctx.contextlevel=50
                INNER JOIN {role_assignments} ra
                        ON ra.contextid=ctx.id
                INNER JOIN {role} r
                        ON ra.roleid = r.id
                WHERE r.shortname IN ('student', 'sds_student')
                GROUP BY c.id
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
                SELECT cm.course courseid, cm.module as moduleid, COUNT(cm.id) cnt
                FROM {course_modules} cm
                GROUP BY cm.course, cm.module
            ) namedmods
                ON namedmods.courseid = c.id

            WHERE c.startdate BETWEEN :startdate and :enddate
            GROUP BY cco.path
SQL;

        return $DB->get_records_sql($sql, array(
            'activity1' => $this->_activity,
            'activity2' => $this->_activity,
            'activity3' => $this->_activity,
            'activity4' => $this->_activity,
            'activity5' => $this->_activity,
            'startdate' => $this->_startdate,
            'enddate' => $this->_enddate
        ));
    }

    /**
     * Returns an array of headings for the report.
     */
    public function get_headings() {
        return array(
            'Category',
            'Total Modules',
            'Total Modules with activity',
            'Ceased Modules',
            'Ceased Modules with activity',
            'Active Modules',
            'Active Modules with activity',
            'Resting Modules',
            'Resting Modules with activity',
            'Inactive Modules',
            'Inactive Modules with activity'
        );
    }
}