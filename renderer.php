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
        return array();
    }
}
