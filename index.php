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

$form = new \report_coursecatcounts\forms\date_select();

// Grab form values if we have any.
if ($data = $form->get_data()) {
    redirect(new \moodle_url('/report/coursecatcounts/index.php', array(
        'startdate' => $data->startdate,
        'enddate' => $data->enddate
    )));
}

$renderer = $PAGE->get_renderer('report_coursecatcounts');

echo $OUTPUT->header();
echo $OUTPUT->heading("Category-Based Course Report");

// Check the form was not submitted this time around.
if (!$form->is_submitted()) {
    $startdate = optional_param('startdate', 0, PARAM_INT);
    $enddate = optional_param('enddate', 0, PARAM_INT);

    // If we dont have a start date or an end date, we cannot continue.
    if ($startdate > 0 && $enddate > 0 && $startdate < $enddate) {
        echo $renderer->run_report($startdate, $enddate);
    }
}

$form->display();

echo $OUTPUT->footer();
