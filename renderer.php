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

require_once($CFG->libdir . "/csvlib.class.php");

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
    public function run_global_report($startdate, $enddate, $csvlink) {
        global $DB;

        $table = new html_table();
        $table->head  = array(
            'Category',
            'Total',
            'Ceased',
            'Total - Ceased',
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

        $report = new \report_coursecatcounts\category_report();
        $data = $report->get_global_data($startdate, $enddate);
        foreach ($data as $row) {
            $category = str_pad($row->name, substr_count($row->path, 1), '-');
            $category = \html_writer::tag('a', $category, array(
                'href' => new \moodle_url('/report/coursecatcounts/index.php', array(
                    'category' => $row->categoryid,
                    'startdate' => $startdate,
                    'enddate' => $enddate
                ))
            ));

            $totalfromcourse = new html_table_cell($row->total_from_course);
            $totalfromcourse->attributes['class'] = 'datacell';
            $totalfromcourse->attributes['catid'] = $row->categoryid;
            $totalfromcourse->attributes['column'] = 'total_from_course';

            $ceased = new html_table_cell($row->ceased);
            $ceased->attributes['class'] = 'datacell';
            $ceased->attributes['catid'] = $row->categoryid;
            $ceased->attributes['column'] = 'ceased';

            $total = new html_table_cell($row->total);
            $total->attributes['class'] = 'datacell';
            $total->attributes['catid'] = $row->categoryid;
            $total->attributes['column'] = 'total';

            $active = new html_table_cell($row->active);
            $active->attributes['class'] = 'datacell';
            $active->attributes['catid'] = $row->categoryid;
            $active->attributes['column'] = 'active';

            $resting = new html_table_cell($row->resting);
            $resting->attributes['class'] = 'datacell';
            $resting->attributes['catid'] = $row->categoryid;
            $resting->attributes['column'] = 'resting';

            $inactive = new html_table_cell($row->inactive);
            $inactive->attributes['class'] = 'datacell';
            $inactive->attributes['catid'] = $row->categoryid;
            $inactive->attributes['column'] = 'inactive';

            $table->data[] = new html_table_row(array(
                new html_table_cell($category),
                $totalfromcourse,
                $ceased,
                $total,
                $active,
                $resting,
                $inactive,
                new html_table_cell($row->per_c_active),
                new html_table_cell($row->guest),
                new html_table_cell($row->keyed),
                new html_table_cell($row->per_c_guest)
            ));
        }

        $csvcell = new html_table_cell($csvlink);
        $csvcell->colspan = 11;
        $table->data[] = new html_table_row(array($csvcell));

        return html_writer::table($table);
    }

    /**
     * This function will output a CSV.
     *
     * @return string HTML to output.
     */
    public function export_global_report($startdate, $enddate) {
        $export = new \csv_export_writer();
        $export->set_filename('Category-Report-' . $startdate . '-' . $enddate);
        $export->add_data(array(
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
        ));

        $report = new \report_coursecatcounts\category_report();
        $data = $report->get_global_data($startdate, $enddate);
        foreach ($data as $row) {
            $category = str_pad($row->name, substr_count($row->path, 1), '-');
            $export->add_data(array(
                s($category),
                s($row->total_from_course),
                s($row->ceased),
                s($row->total),
                s($row->active),
                s($row->resting),
                s($row->inactive),
                s($row->per_c_active),
                s($row->guest),
                s($row->keyed),
                s($row->per_c_guest)
            ));
        }

        $export->download_file();
    }

    /**
     * This function will render a table.
     *
     * @return string HTML to output.
     */
    public function run_category_report($categoryid, $startdate, $enddate, $csvlink) {
        global $DB;

        $table = new html_table();
        $table->head  = array(
            'Course',
            'Status'
        );
        $table->attributes['class'] = 'admintable generaltable';
        $table->data = array();

        $report = new \report_coursecatcounts\category_report();
        $data = $report->get_category_data($categoryid, $startdate, $enddate);
        foreach ($data as $row) {
            $course = \html_writer::tag('a', $row->shortname, array(
                'href' => new \moodle_url('/course/view.php', array(
                    'id' => $row->id
                )),
                'target' => '_blank'
            ));

            $table->data[] = new html_table_row(array(
                new html_table_cell($course),
                new html_table_cell($row->status)
            ));
        }

        $csvcell = new html_table_cell($csvlink);
        $csvcell->colspan = 2;
        $table->data[] = new html_table_row(array($csvcell));

        return html_writer::table($table);
    }

    /**
     * This function will output a CSV.
     *
     * @return string HTML to output.
     */
    public function export_category_report($categoryid, $startdate, $enddate) {
        $export = new \csv_export_writer();
        $export->set_filename('Course-Report-' . $categoryid . '-' . $startdate . '-' . $enddate);
        $export->add_data(array(
            'Course',
            'Status'
        ));

        $report = new \report_coursecatcounts\category_report();
        $data = $report->get_category_data($categoryid, $startdate, $enddate);
        foreach ($data as $row) {
            $export->add_data(array(
                s($row->shortname),
                s($row->status)
            ));
        }

        $export->download_file();
    }
    /**
     * This function will render a table.
     *
     * @return string HTML to output.
     */
    public function beta_run_global_report($csvlink) {
        global $DB;

        $table = new html_table();
        $table->head  = array(
            'Category',
            'Total',
            'Unused',
            'Active',
            'Resting',
            'Empty',
            'Guest Enabled',
            'Guest Passworded'
        );
        $table->attributes['class'] = 'admintable generaltable';
        $table->data = array();

        $report = new \report_coursecatcounts\core();
        foreach ($report->get_categories() as $category) {
            $link = \html_writer::link(new \moodle_url('/report/coursecatcounts/beta.php', array(
                'category' => $category->id
            )), $category->name);

            $totalfromcourse = new html_table_cell($category->count_courses());
            $totalfromcourse->attributes['class'] = 'datacell';
            $totalfromcourse->attributes['catid'] = $category->id;
            $totalfromcourse->attributes['column'] = 'total_from_course';

            $ceased = new html_table_cell($category->count_state(\report_coursecatcounts\course::STATUS_UNUSED));
            $ceased->attributes['class'] = 'datacell';
            $ceased->attributes['catid'] = $category->id;
            $ceased->attributes['column'] = 'ceased';

            $active = new html_table_cell($category->count_state(\report_coursecatcounts\course::STATUS_ACTIVE));
            $active->attributes['class'] = 'datacell';
            $active->attributes['catid'] = $category->id;
            $active->attributes['column'] = 'active';

            $resting = new html_table_cell($category->count_state(\report_coursecatcounts\course::STATUS_RESTING));
            $resting->attributes['class'] = 'datacell';
            $resting->attributes['catid'] = $category->id;
            $resting->attributes['column'] = 'resting';

            $inactive = new html_table_cell($category->count_state(\report_coursecatcounts\course::STATUS_EMPTY));
            $inactive->attributes['class'] = 'datacell';
            $inactive->attributes['catid'] = $category->id;
            $inactive->attributes['column'] = 'inactive';

            $guest = new html_table_cell($category->count_guest());
            $guest->attributes['class'] = 'datacell';
            $guest->attributes['catid'] = $category->id;
            $guest->attributes['column'] = 'guest';

            $guestpwd = new html_table_cell($category->count_guest_passwords());
            $guestpwd->attributes['class'] = 'datacell';
            $guestpwd->attributes['catid'] = $category->id;
            $guestpwd->attributes['column'] = 'guestpwd';

            $table->data[] = new html_table_row(array(
                new html_table_cell($link),
                $totalfromcourse,
                $ceased,
                $active,
                $resting,
                $inactive,
                $guest,
                $guestpwd
            ));
        }

        $csvcell = new html_table_cell($csvlink);
        $csvcell->colspan = 11;
        $table->data[] = new html_table_row(array($csvcell));

        return html_writer::table($table);
    }

    /**
     * This function will output a CSV.
     *
     * @return string HTML to output.
     */
    public function beta_export_global_report() {
        $export = new \csv_export_writer();
        $export->set_filename('Category-Report');
        $export->add_data(array(
            'Category',
            'Total',
            'Unused',
            'Active',
            'Resting',
            'Empty',
            'Guest Enabled',
            'Guest Passworded'
        ));

        $report = new \report_coursecatcounts\core();
        foreach ($report->get_categories() as $category) {
            $export->add_data(array(
                s($category->name),
                s($category->count_courses()),
                s($category->count_state(\report_coursecatcounts\course::STATUS_UNUSED)),
                s($category->count_state(\report_coursecatcounts\course::STATUS_ACTIVE)),
                s($category->count_state(\report_coursecatcounts\course::STATUS_RESTING)),
                s($category->count_state(\report_coursecatcounts\course::STATUS_EMPTY)),
                s($category->count_guest()),
                s($category->count_guest_passwords())
            ));
        }

        $export->download_file();
    }

    /**
     * This function will render a table.
     *
     * @return string HTML to output.
     */
    public function beta_run_category_report($categoryid, $csvlink) {
        global $DB;

        $table = new html_table();
        $table->head  = array(
            'Course',
            'Status'
        );
        $table->attributes['class'] = 'admintable generaltable';
        $table->data = array();

        $report = new \report_coursecatcounts\core();
        $category = $report->get_category($categoryid);
        foreach ($category->get_courses() as $course) {
            $courselink = \html_writer::tag('a', $course->shortname, array(
                'href' => new \moodle_url('/course/view.php', array(
                    'id' => $course->id
                )),
                'target' => '_blank'
            ));

            $table->data[] = new html_table_row(array(
                new html_table_cell($courselink),
                new html_table_cell($course->get_state(true))
            ));
        }

        $csvcell = new html_table_cell($csvlink);
        $csvcell->colspan = 2;
        $table->data[] = new html_table_row(array($csvcell));

        return html_writer::table($table);
    }

    /**
     * This function will output a CSV.
     *
     * @return string HTML to output.
     */
    public function beta_export_category_report($categoryid) {
        $export = new \csv_export_writer();
        $export->set_filename('Course-Report-' . $categoryid);
        $export->add_data(array(
            'Course',
            'Status'
        ));

        $report = new \report_coursecatcounts\category_report();
        $data = $report->get_category_data($categoryid);
        foreach ($data as $row) {
            $export->add_data(array(
                s($row->shortname),
                s($row->status)
            ));
        }

        $export->download_file();
    }
}
