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
 * *** Beta API ***
 *
 * @internal
 */
class category
{
    private $_data;
    private $_courses;

    /**
     * Constructor.
     */
    public function __construct($data) {
        $this->_data = $data;
        $this->_courses = array();
    }

    /**
     * Returns a list of all courses within this category (or below).
     */
    public function get_courses() {
        global $DB;

        if (!empty($this->_courses)) {
            return $this->_courses;
        }

        $sql = <<<SQL
            SELECT c.*
            FROM {course} c
            INNER JOIN {course_categories} cc
                ON cc.path LIKE :path OR cc.path LIKE :path2
SQL;

        $courses = $DB->get_records_sql($sql, array(
            'path' => "{$this->_data->path}",
            'path2' => "{$this->_data->path}/%"
        ));
        foreach ($courses as $course) {
            $this->_courses[] = new course($course);
        }

        return $this->_courses;
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
}
