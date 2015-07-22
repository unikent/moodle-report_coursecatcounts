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

admin_externalpage_setup('coursecatcountsreport', '', null, '', array(
    'pagelayout' => 'report'
));

$PAGE->set_context(\context_system::instance());

$category = optional_param('category', false, PARAM_INT);
$format = optional_param('format', 'screen', PARAM_ALPHA);
if ($format != 'csv') {
    $format = 'screen';
}

$form = new \report_coursecatcounts\forms\date_select();

// Grab form values if we have any.
if (!$category && $data = $form->get_data()) {
    redirect(new \moodle_url('/report/coursecatcounts/beta.php'));
}

$renderer = $PAGE->get_renderer('report_coursecatcounts');

if ($format == 'screen') {
    echo $OUTPUT->header();
    echo $OUTPUT->heading("Category-Based Course Report");
}

// Output to CSV.
if ($format == 'csv') {
    if (!$category) {
        echo $renderer->beta_export_global_report();
    } else {
        echo $renderer->beta_export_category_report($category);
    }
    die;
}

// Output to screen.
if ($format == 'screen') {
    // Download as CSV link.
    $csvlink = \html_writer::tag('a', 'Download as CSV', array(
        'href' => new \moodle_url('/report/coursecatcounts/beta.php', array(
            'category' => $category,
            'format' => 'csv'
        )),
        'style' => 'float: right'
    ));

    if (!$category) {
        echo $renderer->beta_run_global_report($csvlink);
    } else {
        echo $renderer->beta_run_category_report($category, $csvlink);
    }

    // Show a back link for category view.
    if ($category) {
        echo \html_writer::tag('a', 'Back', array(
            'href' => new \moodle_url('/report/coursecatcounts/beta.php')
        ));
    } else {
        echo\html_writer::empty_tag('hr');
    }
}

echo $OUTPUT->footer();
