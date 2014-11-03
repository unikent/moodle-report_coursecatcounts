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

namespace report_coursecatcounts\forms;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/lib/formslib.php');

class date_select extends \moodleform
{
    /**
     * Form definition
     */
    public function definition() {
        $mform =& $this->_form;

        $dategroup = array();

        $range = range(1, 31);
        $dategroup[] = $mform->createElement('select', 'day', '', array_combine($range, $range));

        $range = range(1, 12);
        $months = array();
        for ($i = 1; $i < 13; $i++) {
            $months[$i] = date('F', mktime(0, 0, 0, $i));
        }
        $dategroup[] = $mform->createElement('select', 'month', '', array_combine($range, $months));

        $range = range(2004, ((int)date("Y")) + 2);
        $dategroup[] = $mform->createElement('select', 'year', '', array_combine($range, $range));

        $mform->addGroup($dategroup, 'startdate', 'Start Date');
        $mform->addGroup($dategroup, 'enddate', 'End Date');

        $this->add_action_buttons(false, "Run Report");
    }

    /**
     * Convert dates in data object.
     */
    private function convert_dates($data) {
        $startdate = (object)$data->startdate;
        $data->startdate = strtotime("{$startdate->day}/{$startdate->month}/{$startdate->year}");

        $enddate = (object)$data->enddate;
        $data->enddate = strtotime("{$enddate->day}/{$enddate->month}/{$enddate->year}");

        return $data;
    }

    /**
     * Return proper dates.
     */
    public function get_data() {
        $data = parent::get_data();
        if (!$data) {
            return false;
        }

        return $this->convert_dates($data);
    }

    /**
     * Form validation.
     */
    public function validation($data, $files) {
        $data = $this->convert_dates($data);

        $errors = array();

        if (!$data->startdate) {
            $errors['startdate'] = "Invalid start date";
        }

        if (!$data->enddate) {
            $errors['enddate'] = "Invalid end date";
        }

        if ($data->startdate > $data->enddate || $data->startdate == $data->enddate) {
            $errors['enddate'] = "End date must be greater than start date";
        }

        return $errors;
    }
}
