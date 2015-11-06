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

admin_externalpage_setup('kentoverviewreport', '', null, '', array(
    'pagelayout' => 'report'
));

$PAGE->set_url(new \moodle_url('/report/coursecatcounts/overview.php'));
$PAGE->set_context(\context_system::instance());

$category = optional_param('category', false, PARAM_INT);
$format = optional_param('format', 'screen', PARAM_ALPHA);
if ($format != 'csv') {
    $format = 'screen';
}

// Create Table.
$table = new \local_kent\util\csvtable('kentoverviewreport');
$table->define_headers(array(
    'Module code',
    'Category',
    'No. of students enrolled (SDS/Manual)',
    'Status',
    'Tii assignments',
    'KP block',
    'Grademark assignments',
    'Quiz modules'
));
$table->setup();

// text separate if poss                   From Moodle     No. – manual/SDS? Just SDS?     Active/resting/ceased/inactive      Yes/no      Yes/no                  Yes/no      Yes/no
// (can separate code & title in Excel)                                                                                        Number?    (can add no. from KP)   Number?    Number?


if (!$table->is_downloading()) {
    echo $OUTPUT->header();
    echo $OUTPUT->heading("Kent Overview Report");
}

$done = array();
$report = new \report_coursecatcounts\core();
foreach ($report->get_categories() as $category) {
    foreach ($category->get_courses() as $course) {
        if (in_array($course->shortname, $done)) {
            continue;
        }

        $link = \html_writer::link(new \moodle_url('/course/view.php', array(
            'id' => $course->id
        )), $course->shortname);

        $table->add_data(array(
            $link,
            $category->name,
            $course->get_student_count() . ' (' . $course->get_student_count('sds') . '/' . $course->get_student_count('manual') . ')',
            $course->get_state(true),
            $course->get_activity_count('turnitintooltwo'),
            $course->get_block_count('panopto'),
            'TODO',
            $course->get_activity_count('quiz')
        ), $course->id);

        $done[] = $course->shortname;
    }
}
unset($done);

$table->finish_output();

echo $OUTPUT->footer();
