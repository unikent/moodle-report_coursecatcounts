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

$PAGE->set_context(\context_system::instance());

$form = new \report_coursecatcounts\forms\activity_select();

// Redirect if there is data.
if ($data = $form->get_data()) {
    redirect(new \moodle_url('/report/coursecatcounts/activity.php', array(
        'activity' => $data->activityid
    )));
}

$activity = optional_param('activity', '', PARAM_PLUGIN);
$format = optional_param('format', 'screen', PARAM_ALPHA);
$format = $format == 'csv' ? 'csv' : 'screen';
$table = '';

if (!empty($activity)) {
    $data = array();
    $report = new \report_coursecatcounts\core();
    foreach ($report->get_categories() as $category) {
        $data[] = (object)array(
            'categoryid' => $category->id,
            'name' => $category->name,
            'path' => $category->path,
            'total' => $category->count_courses(),
            'total_activity_count' => $category->count_courses(null, $activity),
            'ceased' => $category->count_courses(\report_coursecatcounts\course::STATUS_UNUSED),
            'ceased_activity_count' => $category->count_courses(\report_coursecatcounts\course::STATUS_UNUSED, $activity),
            'active' => $category->count_courses(\report_coursecatcounts\course::STATUS_ACTIVE),
            'active_activity_count' => $category->count_courses(\report_coursecatcounts\course::STATUS_ACTIVE, $activity),
            'resting' => $category->count_courses(\report_coursecatcounts\course::STATUS_RESTING),
            'resting_activity_count' => $category->count_courses(\report_coursecatcounts\course::STATUS_RESTING, $activity),
            'inactive' => $category->count_courses(\report_coursecatcounts\course::STATUS_EMPTY),
            'inactive_activity_count' => $category->count_courses(\report_coursecatcounts\course::STATUS_EMPTY, $activity)
        );
    }

    // Run CSV.
    $headings = array(
        'Category',
        'Total Modules',
        'Total Modules with activity',
        'Unused Modules',
        'Unused Modules with activity',
        'Active Modules',
        'Active Modules with activity',
        'Resting Modules',
        'Resting Modules with activity',
        'Empty Modules',
        'Empty Modules with activity'
    );

    if ($format == 'csv') {
        require_once($CFG->libdir . '/csvlib.class.php');

        $export = new \csv_export_writer();
        $export->set_filename('Activity-Report-' . $activityname . '-' . strftime(get_string('strftimedatefullshort', 'core_langconfig')));
        $export->add_data(array("Report for '{$activityname}' activity."));
        $export->add_data($headings);
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
        $export->download_file();
        die;
    } else {
        $countcolumns = array(
            'total',
            'total_activity_count',
            'ceased',
            'ceased_activity_count',
            'active',
            'active_activity_count',
            'resting',
            'resting_activity_count',
            'inactive',
            'inactive_activity_count'
        );

        $table = new \html_table();
        $table->head = $headings;
        $table->attributes['class'] = 'admintable generaltable';
        $table->data = array();
        foreach ($data as $row) {
            $category = str_pad($row->name, substr_count($row->path, 1), '-');
            $category = \html_writer::tag('a', $category, array(
                'href' => new \moodle_url('/course/index.php', array(
                    'categoryid' => $row->categoryid
                ))
            ));

            $columns = array(
                new html_table_cell($category)
            );

            foreach ($countcolumns as $column) {
                $cell = new html_table_cell($row->$column);
                $cell->attributes['class'] = "datacell " . $column;
                $cell->attributes['column'] = $column;
                $cell->attributes['catid'] = $row->categoryid;
                $columns[] = $cell;
            }

            $obj = new html_table_row($columns);
            $obj->attributes['class'] = 'datarow';
            $table->data[] = $obj;
        }

        // Download as CSV link.
        $csvlink = \html_writer::tag('a', 'Download as CSV', array(
            'href' => new \moodle_url('/report/coursecatcounts/activity.php', array(
                'activity' => $activity,
                'format' => 'csv'
            )),
            'style' => 'float: right'
        ));
        $csvcell = new html_table_cell($csvlink);
        $csvcell->colspan = 11;
        $table->data[] = new html_table_row(array($csvcell));
    }
}

$PAGE->requires->js_init_call('M.report_activities.init', array($activity), false, array(
    'name' => 'report_coursecatcounts',
    'fullpath' => '/report/coursecatcounts/scripts/activities.js'
));

echo $OUTPUT->header();
echo $OUTPUT->heading("Category-Based Activity Report");

if (!empty($activity)) {
    echo \html_writer::table($table);
    echo \html_writer::empty_tag('hr');
}


echo $OUTPUT->heading('New Report', 4);
$form->display();

echo $OUTPUT->footer();