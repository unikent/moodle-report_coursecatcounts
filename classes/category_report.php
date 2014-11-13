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

class category_report
{
    /**
     * Returns data for the table.
     */
    public function get_global_data($startdate, $enddate) {
        global $DB;

        $cachekey = $startdate . '-' . $enddate;
        $cache = \cache::make('report_coursecatcounts', 'categorycounts');
        if ($content = $cache->get($cachekey)) {
            return $content;
        }

        $sql = <<<SQL
        SELECT
            cco.id as categoryid,
            cco.path,
            cco.name,
            COUNT(c.id) total_from_course,
            yr.period,

            SUM(
                CASE WHEN (stud.cnt < 2 OR stud.cnt IS NULL)
                    THEN 1
                    ELSE 0
                END
            ) ceased,

            COUNT(c.id) - SUM(
                CASE WHEN (stud.cnt < 2 OR stud.cnt IS NULL)
                    THEN 1
                    ELSE 0
                END
            ) total,

            SUM(
                CASE WHEN (stud.cnt > 1 AND stud.cnt IS NOT NULL)
                AND mods.cnt > 0
                AND mods.cnt2 > 0
                AND c.visible=1
                    THEN 1
                    ELSE 0
                END
            ) active,

            SUM(
                CASE WHEN (stud.cnt > 1 AND stud.cnt IS NOT NULL)
                AND mods.cnt > 0 AND mods.cnt2 > 0 AND c.visible=0
                    THEN 1
                    ELSE 0
                END
            ) resting,

            SUM(
                CASE WHEN (stud.cnt > 1 AND stud.cnt IS NOT NULL)
                AND (mods.cnt < 1 OR mods.cnt IS NULL)
                AND (mods.cnt2 < 1 OR mods.cnt2 IS NULL)
                    THEN 1
                    ELSE 0
                END
            ) inactive,

            COALESCE(
                SUM(
                    CASE WHEN (stud.cnt > 1 AND stud.cnt IS NOT NULL)
                    AND mods.cnt > 0
                    AND mods.cnt2 > 0
                    AND c.visible = 1
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
                ),
                'N/A'
            ) per_c_active,

            SUM(
                CASE WHEN en.statcnt > 0
                    THEN 1
                    ELSE 0
                END
            ) guest,

            SUM(
                CASE WHEN en.keycnt > 0
                    THEN 1
                    ELSE 0
                END
            ) keyed,

            COALESCE(
                SUM(
                    CASE WHEN en.statcnt > 0
                        THEN 1
                        ELSE 0
                    END
                ) * 100 / SUM(
                    CASE WHEN (stud.cnt > 1 AND stud.cnt IS NOT NULL)
                    AND mods.cnt > 0
                    AND mods.cnt2 > 0
                    AND c.visible = 1
                        THEN 1
                        ELSE 0
                    END
                ),
                'N/A'
            ) per_c_guest
        FROM {course} c

        INNER JOIN (
            SELECT :startdate start,:enddate ending, 'valid' period
        ) yr
            ON c.startdate BETWEEN yr.start AND yr.ending

        RIGHT OUTER JOIN {course_categories} cc
            ON c.category = cc.id

        RIGHT OUTER JOIN {course_categories} cco
            ON CONCAT(cc.path,'/') LIKE CONCAT(cco.path, '/%')

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
                ON (c.timecreated BETWEEN cm.added - 120 and cm.added + 120)
                AND c.id = cm.course
            GROUP BY c.id
        ) mods
            ON mods.courseid = c.id

        LEFT OUTER JOIN (
            SELECT
                e.courseid,
                COUNT(*) cnt,
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
        ) en
            ON en.courseid = c.id

        WHERE yr.period IS NOT NULL
        GROUP BY yr.period, cco.path
        ORDER BY total_from_course DESC
SQL;

        $data = $DB->get_records_sql($sql, array(
            'startdate' => $startdate,
            'enddate' => $enddate
        ));

        // Because I don't want to ruin the above query...
        // Create a list of known paths.
        $catids = array();
        foreach ($data as $row) {
            $catids[] = $row->categoryid;
        }

        $categories = $DB->get_records('course_categories');
        foreach ($categories as $category) {
            if (!in_array($category->id, $catids)) {
                $newcat = new \stdClass();
                $newcat->categoryid = $category->id;
                $newcat->path = $category->path;
                $newcat->name = $category->name;
                $newcat->total_from_course = 0;
                $newcat->period = 0;
                $newcat->ceased = 0;
                $newcat->total = 0;
                $newcat->active = 0;
                $newcat->resting = 0;
                $newcat->inactive = 0;
                $newcat->per_c_active = 'N/A';
                $newcat->guest = 0;
                $newcat->keyed = 0;
                $newcat->per_c_guest = 'N/A';

                $data[] = $newcat;
            }
        }

        $cache->set($cachekey, $data);

        return $data;
    }

    /**
     * Returns data for the table.
     */
    public function get_category_data($categoryid, $startdate, $enddate) {
        global $DB;

        $cachekey = $categoryid . '-' . $startdate . '-' . $enddate;
        $cache = \cache::make('report_coursecatcounts', 'categorycounts');
        if ($content = $cache->get($cachekey)) {
            return $content;
        }

        $sql = <<<SQL
        SELECT
            c.id,
            c.shortname,
            stud.cnt as student_count,
            mods.cnt as activity_count,
            mods.cnt2 as unique_activity_count,
            CASE WHEN (stud.cnt<2 OR stud.cnt IS NULL)
            THEN 'ceased'
            ELSE
                CASE WHEN (stud.cnt>1 AND stud.cnt IS NOT NULL) AND mods.cnt>0 AND mods.cnt2>0 AND c.visible=1
                THEN 'active'
                ELSE
                    CASE WHEN (stud.cnt>1 AND stud.cnt IS NOT NULL) AND mods.cnt>0 AND mods.cnt2>0 AND c.visible=0
                    THEN 'resting'
                    ELSE
                        CASE WHEN (stud.cnt>1 AND stud.cnt IS NOT NULL) AND (mods.cnt<1 OR mods.cnt IS NULL) AND (mods.cnt2<1 OR mods.cnt2 IS NULL)
                        THEN 'inactive'
                        ELSE
                            'unknown'
                        END
                    END
                END
        END as status

        FROM {course} c

        INNER JOIN {course_categories} cc
            ON c.category = cc.id

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
                ON (c.timecreated BETWEEN cm.added - 120 and cm.added + 120)
                AND c.id = cm.course
            GROUP BY c.id
        ) mods
            ON mods.courseid = c.id

        WHERE c.startdate BETWEEN :startdate AND :enddate
        AND (cc.path LIKE :categorya OR cc.path LIKE :categoryb)
SQL;

        $data = $DB->get_records_sql($sql, array(
            'startdate' => $startdate,
            'enddate' => $enddate,
            'categorya' => "%/" . $categoryid,
            'categoryb' => "%/" . $categoryid . "/%"
        ));

        $cache->set($cachekey, $data);

        return $data;
    }
}
