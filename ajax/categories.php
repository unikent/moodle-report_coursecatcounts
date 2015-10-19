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

require_login();

$context = \context_system::instance();
$PAGE->set_context($context);
if (!has_capability('moodle/site:config', $context)) {
    print_error("Not allowed!");
}

$categoryid = required_param('catid', PARAM_INT);
$ctype = optional_param('ctype', null, PARAM_INT);

$table = new \html_table();
$table->head = array(
    "Module Code",
    "Student Count",
    "Activity Count",
    "Unique Activity Count"
);

$report = new \report_coursecatcounts\core();
$category = $report->get_category($categoryid);
foreach ($category->get_courses() as $course) {
    if ($ctype && $ctype !== $course->get_state()) {
        continue;
    }

    $link = \html_writer::tag('a', $course->shortname, array(
        'href' => new \moodle_url('/course/view.php', array(
            'id' => $course->id
        )),
        'target' => '_blank'
    ));
    $table->data[] = new html_table_row(array(
        $link,
        $course->get_student_count(),
        $course->get_activity_count(),
        $course->get_distinct_activity_count()
    ));
}

echo $OUTPUT->header();
echo json_encode(array(
    "content" => \html_writer::table($table)
));