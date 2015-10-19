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
     * Get data for a specific category.
     */
    public function get_modules_for_category($catid) {
        global $DB;

        $cachekey = 'category-' . $catid . '-' . $this->_activity . '-' . $this->_startdate . '-' . $this->_enddate;
        $cache = \cache::make('report_coursecatcounts', 'activitycounts');
        if ($content = $cache->get($cachekey)) {
            return $content;
        }

        $categories = $DB->get_records('course_categories');

        $data = array();
        $moduledata = $this->get_modules();
        foreach ($moduledata as $module) {
            $category = $categories[$module->catid];
            $path = explode('/', $category->path);

            if (in_array($catid, $path)) {
                $data[] = $module;
            }
        }

        $cache->set($cachekey, $data);
        return $data;
    }

    /**
     * Returns data.
     */
    public function get_data() {
        global $DB;

        $cachekey = 'global-' . $this->_activity . '-' . $this->_startdate . '-' . $this->_enddate;
        $cache = \cache::make('report_coursecatcounts', 'activitycounts');
        if ($content = $cache->get($cachekey)) {
            return $content;
        }

        $data = array();

        // Initialise data.
        $categories = $DB->get_records('course_categories');
        foreach ($categories as $category) {
            $data[$category->id] = array(
                'categoryid' => $category->id,
                'name' => $category->name,
                'path' => $category->path,
                'total' => 0,
                'total_activity_count' => 0,
                'ceased' => 0,
                'ceased_activity_count' => 0,
                'active' => 0,
                'active_activity_count' => 0,
                'resting' => 0,
                'resting_activity_count' => 0,
                'inactive' => 0,
                'inactive_activity_count' => 0
            );
        }

        // Update categories with module info.
        $moduledata = $this->get_modules();
        foreach ($moduledata as $module) {
            // Grab related category.
            $category = $categories[$module->catid];
            $path = explode('/', $category->path);

            foreach ($path as $catid) {
                if (!isset($data[$catid])) {
                    continue;
                }

                $data[$catid]['total'] += 1;
                foreach ($module as $col => $val) {
                    $val = (int)$val;
                    if (isset($data[$catid][$col]) && $val > 0) {
                        $data[$catid][$col] += $val;
                    }
                }
            }
        }

        // Convert data to stdClasses.
        foreach ($data as $k => $v) {
            $data[$k] = (object)$v;
        }

        uasort($data, function ($a, $b) {
            return $a->total < $b->total;
        });

        $cache->set($cachekey, $data);
        return $data;
    }

    /**
     * Returns specific modules.
     */
    public function get_modules() {
        global $DB;

        $cachekey = 'modules-' . $this->_activity . '-' . $this->_startdate . '-' . $this->_enddate;
        $cache = \cache::make('report_coursecatcounts', 'activitycounts');
        if ($content = $cache->get($cachekey)) {
            return $content;
        }

        $datesql = '';
        $params = array(
            'activity' => $this->_activity
        );

        if ($this->_startdate < $this->_enddate) {
            $datesql = 'WHERE c.startdate BETWEEN :startdate and :enddate';
            $params['startdate'] = $this->_startdate;
            $params['enddate'] = $this->_enddate;
        }

        $sql = <<<SQL
            SELECT
                c.id,
                c.shortname,
                cc.id as catid,
                stud.cnt as student_count,
                mods.cnt as activity_count,
                mods.cnt2 as unique_activity_count,

                /* Total Modules with activity */
                CASE WHEN (namedmods.cnt > 0)
                    THEN 1
                    ELSE 0
                END total_activity_count,

                /* Ceased Modules */
                CASE WHEN (stud.cnt < 2)
                    THEN 1
                    ELSE 0
                END ceased,

                /* Ceased Modules with activity */
                CASE WHEN (stud.cnt < 2)
                AND (namedmods.cnt > 0)
                    THEN 1
                    ELSE 0
                END ceased_activity_count,

                /* Active Modules */
                CASE WHEN (stud.cnt > 1)
                AND mods.cnt > 2
                AND mods.cnt2 > 0
                AND c.visible = 1
                    THEN 1
                    ELSE 0
                END active,

                /* Active Modules with activity */
                CASE WHEN (stud.cnt > 1)
                AND mods.cnt > 2
                AND mods.cnt2 > 0
                AND c.visible = 1
                AND namedmods.cnt > 0
                    THEN 1
                    ELSE 0
                END active_activity_count,

                /* Resting Modules */
                CASE WHEN (stud.cnt > 1)
                AND mods.cnt > 2
                AND mods.cnt2 > 0
                AND c.visible = 0
                    THEN 1
                    ELSE 0
                END resting,

                /* Resting Modules with activity */
                CASE WHEN (stud.cnt > 1)
                AND mods.cnt > 2
                AND mods.cnt2 > 0
                AND c.visible = 0
                AND namedmods.cnt > 0
                    THEN 1
                    ELSE 0
                END resting_activity_count,

                /* Inactive Modules */
                CASE WHEN (stud.cnt > 1)
                AND (mods.cnt < 2)
                AND (mods.cnt2 < 1)
                    THEN 1
                    ELSE 0
                END inactive,

                /* Inactive Modules with activity */
                CASE WHEN (stud.cnt > 1)
                AND (mods.cnt < 2)
                AND (mods.cnt2 < 1)
                AND namedmods.cnt > 0
                    THEN 1
                    ELSE 0
                END inactive_activity_count

            FROM {course} c
            INNER JOIN {course_categories} cc
                ON cc.id = c.category

            LEFT OUTER JOIN (
                SELECT c.id as courseid, COALESCE(COUNT(ra.id), 0) cnt
                FROM {course} c
                INNER JOIN {context} ctx
                        ON ctx.instanceid=c.id
                        AND ctx.contextlevel=50
                LEFT OUTER JOIN {role_assignments} ra
                        ON ra.contextid=ctx.id
                LEFT OUTER JOIN {role} r
                        ON ra.roleid = r.id AND r.shortname IN ('student', 'sds_student')
                GROUP BY c.id
            ) stud
                ON stud.courseid = c.id

            LEFT OUTER JOIN (
                SELECT c.id as courseid, COALESCE(COUNT(cm.id), 0) cnt, COALESCE(COUNT(DISTINCT cm.module), 0) cnt2
                FROM {course} c
                LEFT OUTER JOIN {course_modules} cm
                    ON c.id = cm.course
                GROUP BY c.id
            ) mods
                ON mods.courseid = c.id

            LEFT OUTER JOIN (
                SELECT cm.course courseid, COUNT(cm.id) cnt
                FROM {course_modules} cm
                WHERE cm.module = :activity
                GROUP BY cm.course
            ) namedmods
                ON namedmods.courseid = c.id

            $datesql
            GROUP BY c.id
SQL;

        $data = $DB->get_records_sql($sql, $params);

        $cache->set($cachekey, $data);
        return $data;
    }

    /**
     * Returns an array of headings for the report.
     */
    public function get_headings() {
        return array(
            'Category',
            'Total Modules',
            'Total Modules with activity',
            'Unused Modules',
            'Unused Modules with activity',
            'Active Modules',
            'Active Modules with activity',
            'Resting Modules',
            'Resting Modules with activity',
            'Empty Modules',
            'Empty Modules with activity'
        );
    }
}