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
require_once($CFG->libdir . "/coursecatlib.php");

admin_externalpage_setup('kentoverviewreport', '', null, '', array(
    'pagelayout' => 'report'
));

$excludemanual = optional_param('excludemanual', false, PARAM_BOOL);

$PAGE->set_url(new \moodle_url('/report/coursecatcounts/overview.php', array('excludemanual' => $excludemanual)));
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
    'No. of students enrolled',
    'Status',
    'Tii inboxes',
    'Tii submissions',
    'Tii grademark inboxes',
    'Tii grademark assignments',
    'KP block',
    'KP recordings',
    'Quiz modules'
));
$table->setup();

if (!$table->is_downloading()) {
    $PAGE->requires->js("/report/coursecatcounts/scripts/overview.js");

    echo $OUTPUT->header();
    echo $OUTPUT->heading("Kent Overview Report");

    $tagparams = array(
        'type' => 'checkbox',
        'id' => 'excludemanual',
        'name' => 'excludemanual',
        'value' => true
    );

    if ($excludemanual) {
        $tagparams['checked'] = 'checked';
    }

    echo \html_writer::empty_tag('input', $tagparams) . ' Exclude manual';
}

$categories = \coursecat::make_categories_list();

$done = array();
$report = new \report_coursecatcounts\core();
foreach ($report->get_categories() as $category) {
    foreach ($category->get_courses() as $course) {
        if (in_array($course->shortname, $done)) {
            continue;
        }

        $done[] = $course->shortname;

        if ($excludemanual && $course->is_manual()) {
            continue;
        }

        $link = \html_writer::link(new \moodle_url('/course/view.php', array(
            'id' => $course->id
        )), $course->shortname);

        $panoptoblocks = $course->get_block_count('panopto');

        $table->add_data(array(
            $table->is_downloading() ? $course->shortname : $link,
            $categories[$course->category],
            $course->get_student_count(),
            $course->get_state(true),
            $course->get_activity_count('turnitintooltwo'),
            $course->count_turnitin_submissions(),
            $course->count_grademark_inboxes(),
            $course->count_turnitin_grades(),
            $panoptoblocks > 0 ? 'Yes' : 'No',
            $panoptoblocks > 0 ? $course->count_panopto_recordings() : 0,
            $course->get_activity_count('quiz')
        ), $course->id);
    }
}

$table->finish_output();

echo $OUTPUT->footer();
