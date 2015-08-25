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

admin_externalpage_setup('manualcoursereport', '', null, '', array(
    'pagelayout' => 'report'
));

echo $OUTPUT->header();
echo $OUTPUT->heading("Manual Course Report");

$sql = <<<SQL
	SELECT c.id, cat.name as category, c.shortname, c.fullname
	FROM {course} c
	INNER JOIN {course_categories} cat
		ON cat.id = c.category
		AND cat.id <> :cid
		AND cat.id <> 0
	LEFT OUTER JOIN {connect_course} cc
		ON cc.mid=c.id
	WHERE cc.id IS NULL
	GROUP BY c.id
SQL;

$cat = \tool_cat\recyclebin::get_category();
$rs = $DB->get_recordset_sql($sql, array(
	'cid' => $cat->id
));

$table = new \html_table();
$table->head = array("Course", "Category");
$table->attributes['class'] = 'admintable generaltable';
$table->data = array();

foreach ($rs as $record) {
	$courseurl = new \moodle_url('/course/view.php', array(
		'id' => $record->id
	));
    $coursecell = new html_table_cell(\html_writer::link($courseurl, "{$record->shortname}: {$record->fullname}"));

	$table->data[] = array($coursecell, $record->category);
}

$rs->close();

if (count($table->data) > 0) {
	echo \html_writer::table($table);
} else {
	echo \html_writer::tag('p', 'No manual courses found.');
}

echo $OUTPUT->footer();
