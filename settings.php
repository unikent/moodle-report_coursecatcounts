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

/**
 * Report settings
 *
 * @package    report
 * @subpackage connect
 * @copyright  2009 Petr Skoda
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

debugging("report_coursecatcounts is deprecated");

$ADMIN->add('reports', new admin_externalpage(
    'coursecatcountsreport',
    'Category-Based Course Counts',
    new \moodle_url("/report/coursecatcounts/index.php")
));

$ADMIN->add('reports', new admin_externalpage(
    'coursemodulecountsreport',
    'Category-Based Activity Counts',
    new \moodle_url("/report/coursecatcounts/activity.php")
));

$ADMIN->add('reports', new admin_externalpage(
    'courseturnitincountsreport',
    'Category-Based Turnitin Counts',
    new \moodle_url("/report/coursecatcounts/turnitin.php")
));

$ADMIN->add('reports', new admin_externalpage(
    'manualcoursereport',
    'Manual Courses',
    new \moodle_url("/report/coursecatcounts/manual_courses.php")
));

$ADMIN->add('reports', new admin_externalpage(
    'kentoverviewreport',
    'Kent Overview',
    new \moodle_url("/report/coursecatcounts/overview.php")
));

$settings = null;
