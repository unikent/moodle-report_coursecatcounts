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

define('CLI_SCRIPT', true);

require_once(dirname(__FILE__) . '/../../../config.php');

$timenow = time();
mtrace("Server Time: " . date('r', $timenow));

$cache = \cache::make('report_coursecatcounts', 'coursefastinfo');
$cache->purge();

// Pre-generate the report caches.
$report = new \report_coursecatcounts\core();
foreach ($report->get_categories() as $category) {
    $starttime = microtime();
    mtrace(" -> Processing category {$category->id}");

    $category->count_courses();
    $category->count_courses(\report_coursecatcounts\course::STATUS_UNUSED);
    $category->count_courses(\report_coursecatcounts\course::STATUS_ACTIVE);
    $category->count_courses(\report_coursecatcounts\course::STATUS_RESTING);
    $category->count_courses(\report_coursecatcounts\course::STATUS_EMPTY);
    $category->count_guest();
    $category->count_guest_passwords();

    $difftime = microtime_diff($starttime, microtime());
    mtrace("    Execution took {$difftime} seconds");
}
