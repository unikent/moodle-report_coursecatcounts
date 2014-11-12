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
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('coursemodulecountsreport', '', null, '', array(
    'pagelayout' => 'report'
));

$form = new \report_coursecatcounts\forms\activity_select();

// Redirect if there is data.
if ($data = $form->get_data()) {
    redirect(new \moodle_url('/report/coursecatcounts/activity.php', array(
        'activity' => $data->activityid,
        'start' => $data->startdate,
        'end' => $data->enddate
    )));
}

$activity = optional_param('activity', 0, PARAM_INT);
$startdate = optional_param('start', 0, PARAM_INT);
$enddate = optional_param('end', 0, PARAM_INT);
$format = optional_param('format', 'screen', PARAM_ALPHA);
$format = $format == 'csv' ? 'csv' : 'screen';

if ($activity > 0) {
    $activityname = $DB->get_field('modules', 'name', array(
        'id' => $activity
    ));

    $report = new \report_coursecatcounts\activity_report($activity, $startdate, $enddate);
    $data = $report->get_data();

    // Run CSV.
    if ($format == 'csv') {
        $export = new \csv_export_writer();
        $export->set_filename('Activity-Report-' . $activityname . '-' . $startdate . '-' . $enddate);
        $export->add_data(array("Report for $activityname."));
        $export->add_data($report->get_headings());
        foreach ($data as $row) {
            $export->add_data(array(
                $row->name,
                $row->total,
                $row->total_activity_count,
                $row->ceased,
                $row->ceased_activity_count,
                $row->active,
                $row->active_activity_count,
                $row->resting,
                $row->resting_activity_count,
                $row->inactive,
                $row->inactive_activity_count
            ));
        }
    } else {
        $table = new \html_table();
        $table->head = $report->get_headings();
        $table->attributes['class'] = 'admintable generaltable';
        $table->data = array();
        foreach ($data as $row) {
            $category = str_pad($row->name, substr_count($row->path, 1), '-');
            $category = \html_writer::tag('a', $category, array(
                'href' => new \moodle_url('/course/index.php', array(
                    'categoryid' => $row->categoryid
                ))
            ));

            $table->data[] = new html_table_row(array(
                new html_table_cell($category),
                new html_table_cell($row->total),
                new html_table_cell($row->total_activity_count),
                new html_table_cell($row->ceased),
                new html_table_cell($row->ceased_activity_count),
                new html_table_cell($row->active),
                new html_table_cell($row->active_activity_count),
                new html_table_cell($row->resting),
                new html_table_cell($row->resting_activity_count),
                new html_table_cell($row->inactive),
                new html_table_cell($row->inactive_activity_count)
            ));
        }
    }
}

echo $OUTPUT->header();
echo $OUTPUT->heading("Category-Based Activity Report");

if ($startdate > 0 || $enddate > 0) {
    echo \html_writer::table($table);
    echo \html_writer::empty_tag('hr');
    echo $OUTPUT->heading('New Report', 4);
}

$form->display();

echo $OUTPUT->footer();