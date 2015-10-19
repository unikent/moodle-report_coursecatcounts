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

admin_externalpage_setup('courseturnitincountsreport', '', null, '', array(
    'pagelayout' => 'report'
));

$PAGE->set_context(\context_system::instance());

$format = optional_param('format', 'screen', PARAM_ALPHA);
$format = $format == 'csv' ? 'csv' : 'screen';
$table = '';

$data = array();
$report = new \report_coursecatcounts\core();
foreach ($report->get_categories() as $category) {
    $data[] = (object)array(
        'categoryid' => $category->id,
        'name' => $category->name,
        'path' => $category->path,
        'total' => $category->count_courses(),
        'total_tii_count' => $category->count_courses(null, 'turnitintooltwo'),
        'total_grademark_count' => \report_coursecatcounts\turnitin::count_grademark($category)
    );
}

// Run CSV.
$headings = array(
    'Category',
    'Total',
    'Total with Turnitin',
    'Total with Grademark',
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
            $row->total_tii_count,
            $row->total_grademark_count
        ));
    }
    $export->download_file();
    die;
} else {
    $countcolumns = array(
        'total' => '',
        'total_tii_count' => '',
        'total_grademark_count' => ''
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

        foreach ($countcolumns as $column => $status) {
            $cell = new html_table_cell($row->$column);
            $cell->attributes['class'] = "datacell " . $column;
            if ($status) {
                $cell->attributes['column'] = $status;
            }
            $cell->attributes['catid'] = $row->categoryid;
            $columns[] = $cell;
        }

        $obj = new html_table_row($columns);
        $obj->attributes['class'] = 'datarow';
        $table->data[] = $obj;
    }

    // Download as CSV link.
    $csvlink = \html_writer::tag('a', 'Download as CSV', array(
        'href' => new \moodle_url('/report/coursecatcounts/turnitin.php', array(
            'format' => 'csv'
        )),
        'style' => 'float: right'
    ));
    $csvcell = new html_table_cell($csvlink);
    $csvcell->colspan = 11;
    $table->data[] = new html_table_row(array($csvcell));
}

echo $OUTPUT->header();
echo $OUTPUT->heading("Category-Based Turnitin Report");

echo \html_writer::table($table);

echo $OUTPUT->footer();