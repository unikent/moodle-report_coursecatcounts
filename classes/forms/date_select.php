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

        $fields = array(
            'startdate' => 'Start Date',
            'enddate' => 'End Date'
        );

        foreach ($fields as $name => $text) {
            $dategroup = array();

            $range = range(1, 31);
            $dategroup[] = $mform->createElement('select', $name . '_day', '', array_combine($range, $range));

            $range = range(1, 12);
            $months = array();
            for ($i = 1; $i < 13; $i++) {
                $months[$i] = date('F', mktime(0, 0, 0, $i));
            }
            $dategroup[] = $mform->createElement('select', $name . '_month', '', array_combine($range, $months));

            $range = range(2004, ((int)date("Y")) + 2);
            $dategroup[] = $mform->createElement('select', $name . '_year', '', array_combine($range, $range));


            $mform->addGroup($dategroup, $name, $text);
        }
        
        $mform->addElement('checkbox', 'showall', '', 'Show all (ignore dates)');

        $this->add_action_buttons(false, "Run Report");
    }

    /**
     * Set form data from times.
     */
    public function set_from_time($start, $end) {
        $dates = array(
            'startdate' => $start,
            'enddate' => $end,
        );

        $data = array();
        foreach ($dates as $field => $time) {
            $time = strftime('%d/%m/%Y', $time);
            $parts = explode('/', $time);
            if (count($parts) > 0) {
                $data["{$field}[{$field}_day]"] = $parts[0];
                $data["{$field}[{$field}_month]"] = $parts[1];
                $data["{$field}[{$field}_year]"] = $parts[2];
            }
        }

        $this->set_data($data);
    }

    /**
     * Convert dates in data object.
     */
    private function convert_dates($data) {
        $startdate = (object)$data->startdate;
        if ($startdate) {
            $data->startdate = strtotime("{$startdate->startdate_day}/{$startdate->startdate_month}/{$startdate->startdate_year}");
        }

        $enddate = (object)$data->enddate;
        if ($enddate) {
            $data->enddate = strtotime("{$enddate->enddate_day}/{$enddate->enddate_month}/{$enddate->enddate_year}");
        }

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

        if (isset($data->showall) && $data->showall == 1) {
            $data->startdate = 0;
            $data->enddate = 0;
            return $data;
        }

        return $this->convert_dates($data);
    }

    /**
     * Form validation.
     */
    public function validation($data, $files) {
        $data = (object)$data;

        if (isset($data->showall) && $data->showall == 1) {
            return array();
        }

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
