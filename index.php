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

$renderer = $PAGE->get_renderer('report_coursecatcounts');

echo $OUTPUT->header();
echo $OUTPUT->heading("Category-Based Course Report");

$form = new \report_coursecatcounts\forms\date_select();
$data = $form->get_data();

// Sanity check.
if ($data) {
    $startdate = (object)$data->startdate;
    $startdate = strtotime("{$startdate->day}/{$startdate->month}/{$startdate->year}");

    $enddate = (object)$data->enddate;
    $enddate = strtotime("{$enddate->day}/{$enddate->month}/{$enddate->year}");

    if (!$startdate) {
        echo $OUTPUT->notification("Invalid start date!");
        $data = false;
    }

    if (!$enddate) {
        echo $OUTPUT->notification("Invalid end date!");
        $data = false;
    }

    if ($startdate > $enddate || $startdate == $enddate) {
        echo $OUTPUT->notification("End date must be greater than start date!");
        $data = false;
    }

    if ($data) {
        echo $renderer->run_report($startdate, $enddate);
    }
}

if (!$data) {
    $form->display();
}

echo $OUTPUT->footer();
