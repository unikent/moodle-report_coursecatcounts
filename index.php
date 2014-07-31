<?php
// This file is part of Moodle http://moodle.org/
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

require_once(dirname(__FILE__) . '/../../config.php');

admin_externalpage_setup('coursecatcountsreport', '', null, '', array(
    'pagelayout' => 'report'
));

// This is the SQL this report needs to replace.
$sql = <<<SQL
    SELECT period, CONCAT(REPEAT('---',
        LENGTH(
            REPLACE(
                REPLACE(
                    REPLACE(
                        REPLACE(
                            REPLACE(
                                REPLACE(
                                    REPLACE(
                                        REPLACE(
                                            REPLACE(
                                                REPLACE(cco.path,'0',''),
                                                '1',''
                                            ),
                                        '2',''
                                    ),
                                '3',''
                            ),
                        '4',''),
                    '5',''),
                '6',''),
            '7',''),
        '8',''),
    '9','')) - 1),cco.name) name,

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
        CASE WHEN (stud.cnt > 1 AND stud.cnt IS NOT NULL) AND mods.cnt > 0 AND mods.cnt2 > 0 AND c.visible=1
            THEN 1
            ELSE 0
        END
    ) Active,

    SUM(
        CASE WHEN (stud.cnt > 1 AND stud.cnt IS NOT NULL) AND mods.cnt > 0 AND mods.cnt2 > 0 AND c.visible=0
            THEN 1
            ELSE 0
        END
    ) Resting,

    SUM(
        CASE WHEN (stud.cnt > 1 AND stud.cnt IS NOT NULL) AND (mods.cnt < 1 OR mods.cnt IS NULL) AND (mods.cnt2 < 1 OR mods.cnt2 IS NULL)
            THEN 1
            ELSE 0
        END
    ) Inactive,

    SUM(
        CASE WHEN (stud.cnt > 1 AND stud.cnt IS NOT NULL) AND mods.cnt > 0 AND mods.cnt2 > 0 AND c.visible=1
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

    JOIN (
        SELECT UNIX_TIMESTAMP('2011-09-01') start, UNIX_TIMESTAMP('2012-09-01') ending, '2013-14' period
        UNION
        SELECT UNIX_TIMESTAMP('2011-09-01') start, UNIX_TIMESTAMP('2011-12-31') ending, '2013-14 AUT' period
        UNION
        SELECT UNIX_TIMESTAMP('2012-01-01') start, UNIX_TIMESTAMP('2012-09-01') ending, '2013-14 SPR' period
        UNION
        SELECT UNIX_TIMESTAMP('2012-09-01') start, UNIX_TIMESTAMP('2013-09-01') ending, '2013-14' period
        UNION
        SELECT UNIX_TIMESTAMP('2012-09-01') start, UNIX_TIMESTAMP('2012-12-31') ending, '2013-14 AUT' period
        UNION
        SELECT UNIX_TIMESTAMP('2013-01-01') start, UNIX_TIMESTAMP('2013-09-01') ending, '2013-14 SPR' period
        UNION
        SELECT UNIX_TIMESTAMP('2013-09-01') start, UNIX_TIMESTAMP('2014-09-01') ending, '2013-14' period
        UNION
        SELECT UNIX_TIMESTAMP('2013-09-01') start, UNIX_TIMESTAMP('2013-12-31') ending, '2013-14 AUT' period
        UNION
        SELECT UNIX_TIMESTAMP('2014-01-01') start, UNIX_TIMESTAMP('2014-09-01') ending, '2013-14 SPR' period
        UNION
        SELECT UNIX_TIMESTAMP('2014-09-01') start, UNIX_TIMESTAMP('2015-09-01') ending, '2014-15' period
        UNION
        SELECT UNIX_TIMESTAMP('2014-09-01') start, UNIX_TIMESTAMP('2014-12-31') ending, '2014-15 AUT' period
        UNION
        SELECT UNIX_TIMESTAMP('2015-01-01') start, UNIX_TIMESTAMP('2015-09-01') ending, '2014-15 SPR' period
    ) yr
        ON c.startdate BETWEEN yr.start and yr.ending

    RIGHT OUTER JOIN {course_categories} cc
        ON c.category = cc.id

    RIGHT OUTER JOIN {course_categories} cco
        ON CONCAT(cc.path,'/') LIKE CONCAT(cco.path, '/%') AND cco.name <> 'Removed'

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

    GROUP BY period, cco.path
SQL;


echo $OUTPUT->header();
echo $OUTPUT->heading("Category-Based Course Report");

echo \html_writer::tag('p', 'Currently Under Development.');

echo $OUTPUT->footer();
