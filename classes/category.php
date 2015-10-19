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
 * Report category.
 *
 * @internal
 */
class category
{
    use \local_kent\traits\datapod;

    private $courses;

    /**
     * Constructor.
     */
    public function __construct($data) {
        $this->set_data($data);
    }

    /**
     * Returns a list of all courses within this category (or below).
     */
    public function get_courses() {
        global $DB;

        if (isset($this->courses)) {
            return $this->courses;
        }

        $data = array();

        $sql = <<<SQL
            SELECT c.*
            FROM {course} c
            INNER JOIN {course_categories} cc
                ON c.category=cc.id AND (cc.path LIKE :path OR cc.path LIKE :path2)
            GROUP BY c.id
SQL;

        $courses = $DB->get_records_sql($sql, array(
            'path' => "{$this->path}",
            'path2' => "{$this->path}/%"
        ));
        foreach ($courses as $course) {
            $data[] = new course($course);
        }

        $this->courses = $data;

        return $data;
    }

    /**
     * Returns a count of all courses within this category (or below).
     */
    public function count_courses() {
        $courses = $this->get_courses();
        return count($courses);
    }

    /**
     * Count all courses with a given state.
     */
    public function count_state($state) {
        $total = 0;

        // Loop and count.
        $courses = $this->get_courses();
        foreach ($courses as $course) {
            if ($course->get_state() == $state) {
               $total++;
            }
        }

        return $total;
    }

    /**
     * Count all courses with guest access.
     */
    public function count_guest() {
        $total = 0;

        // Loop and count.
        $courses = $this->get_courses();
        foreach ($courses as $course) {
            if ($course->is_guest_enabled()) {
               $total++;
            }
        }

        return $total;
    }

    /**
     * Count all courses with guest access passworded.
     */
    public function count_guest_passwords() {
        $total = 0;

        // Loop and count.
        $courses = $this->get_courses();
        foreach ($courses as $course) {
            if ($course->has_guest_password()) {
               $total++;
            }
        }

        return $total;
    }
}
