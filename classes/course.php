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
    private $_id;

    /**
     * Constructor.
     */
    public function __construct($id) {
        $this->_id = $id;
    }

    /**
     * Returns the course's state:
     *  - Active: Has students/content and is visible.
     *  - Resting: Has students/content but is not visible.
     *  - Empty: Has students but no content.
     *  - Unused: Has no students.
     */
    public function get_state() {
        // TODO.
    }
}
