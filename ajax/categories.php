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
$startdate = required_param('startdate', PARAM_INT);
$enddate = required_param('enddate', PARAM_INT);

$table = new \html_table();
$table->head = array(
    "Module Code",
    "Student Count",
    "Activity Count",
    "Unique Activity Count"
);

$report = new \report_coursecatcounts\category_report();
$data = $report->get_category_data($catid, $startdate, $enddate);

foreach ($data as $row) {
    $valid = false;

    switch ($ctype) {
        case 'total_from_course':
            $valid = true;
        break;

        case 'ceased':
            $valid = $row->status == 'ceased';
        break;

        case 'total':
            $valid = $row->status != 'ceased';
        break;

        case 'active':
            $valid = $row->status == 'active';
        break;

        case 'resting':
            $valid = $row->status == 'resting';
        break;

        case 'inactive':
            $valid = $row->status == 'inactive';
        break;
    }

    if ($valid) {
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