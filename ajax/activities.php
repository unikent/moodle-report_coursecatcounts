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

define('AJAX_SCRIPT', true);

require_once(dirname(__FILE__) . '/../../../config.php');

if (!has_capability('moodle/site:config', \context_system::instance())) {
    print_error("Not allowed!");
}

$catid = required_param('catid', PARAM_INT);
$ctype = required_param('ctype', PARAM_ALPHAEXT);
$activity = required_param('activity', PARAM_INT);
$startdate = required_param('startdate', PARAM_INT);
$enddate = required_param('enddate', PARAM_INT);

$table = new \html_table();
$table->head = array(
    "Module Code",
    "Student Count",
    "Activity Count",
    "Unique Activity Count"
);

$report = new \report_coursecatcounts\activity_report($activity, $startdate, $enddate);
$data = $report->get_modules_for_category($catid);

foreach ($data as $row) {
    if ($ctype == 'total' || $row->$ctype > 0) {
        $course = \html_writer::tag('a', $row->shortname, array(
            'href' => new \moodle_url('/course/view.php', array(
                'id' => $row->id
            )),
            'target' => '_blank'
        ));
        $table->data[] = new html_table_row(array(
            $course,
            $row->student_count,
            $row->activity_count,
            $row->unique_activity_count
        ));
    }
}

echo $OUTPUT->header();
echo json_encode(array(
    "content" => \html_writer::table($table)
));