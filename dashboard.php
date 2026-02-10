<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Dashboard for Storage Guard Plugin
 *
 * @package     local_storage_guard
 * @copyright   2026 Wafaa Mansour <eng.wafaa.hamdy@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once(__DIR__ . '/../../config.php');

require_login();
require_capability('moodle/site:config', context_system::instance());

$filter_category = optional_param('category', 0, PARAM_INT);
$filter_lock     = optional_param('lockstatus', -1, PARAM_INT);
$export_csv      = optional_param('exportcsv', false, PARAM_BOOL);

// 1. DATA FETCHING
$params = [];
$where = [];
if ($filter_category > 0) { $where[] = "c.category = :category"; $params['category'] = $filter_category; }
if ($filter_lock !== -1) { $where[] = "lsg.is_locked = :islocked"; $params['islocked'] = $filter_lock; }
$where_sql = !empty($where) ? ' WHERE ' . implode(' AND ', $where) : '';

$sql = "SELECT lsg.*, c.fullname, cc.name as categoryname, d.value as custom_quota
          FROM {local_storage_guard} lsg
          JOIN {course} c ON c.id = lsg.courseid
          JOIN {course_categories} cc ON cc.id = c.category
     LEFT JOIN {customfield_field} f ON f.shortname = 'custom_quota_mb'
     LEFT JOIN {customfield_data} d ON (d.instanceid = c.id AND d.fieldid = f.id)
          $where_sql
      ORDER BY lsg.last_notified DESC";

$records = $DB->get_records_sql($sql, $params) ?: []; // Fixes count() Error by ensuring it's an array.

// 2. CSV EXPORT (Must be called before ANY output/header)
if ($export_csv && confirm_sesskey()) {
    $filename = 'storage_report_' . date('Ymd') . '.csv';
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="'.$filename.'"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Course', 'Category', 'Status', 'Last Checked']);
    foreach ($records as $r) {
        fputcsv($out, [$r->fullname, $r->categoryname, $r->is_locked ? 'Locked' : 'Active', userdate($r->last_notified)]);
    }
    fclose($out);
    exit;
}

// 3. START PAGE RENDERING
echo $OUTPUT->header();

// 4. HANDLE SCAN RESULTS (Avoid redirect error by providing a 'Continue' button)
if (optional_param('forcescan', false, PARAM_BOOL) && confirm_sesskey()) {
    $task = \core\task\manager::get_scheduled_task('\local_storage_guard\task\quota_enforcement_task');
    if ($task) {
        echo html_writer::start_tag('pre', ['class' => 'p-3 bg-dark text-white rounded']);
        $task->execute();
        echo "\nScan complete.";
        echo html_writer::end_tag('pre');
    }
    echo $OUTPUT->continue_button(new moodle_url('/local/storage_guard/dashboard.php'));
    echo $OUTPUT->footer();
    exit;
}

// 5. PREPARE DATA FOR TEMPLATE
$categories = $DB->get_records_menu('course_categories', null, 'name ASC', 'id, name');
$total_locked = 0;
$courses_data = [];

foreach ($records as $record) {
    if ($record->is_locked) $total_locked++;
    $courses_data[] = [
        'url' => (new moodle_url('/course/edit.php', ['id' => $record->courseid]))->out(),
        'name' => format_string($record->fullname),
        'categoryname' => format_string($record->categoryname),
        'quota' => !empty($record->custom_quota) ? $record->custom_quota . ' MB' : 'Default',
        'statusclass' => $record->is_locked ? 'badge-danger' : 'badge-success',
        'statuslabel' => $record->is_locked ? 'LOCKED' : 'ACTIVE',
        'time' => userdate($record->last_notified, '%d %b %Y, %I:%M %p')
    ];
}

$renderdata = [
    'total_courses' => count($records),
    'total_locked' => $total_locked,
    'scanurl' => new moodle_url('/local/storage_guard/dashboard.php', ['forcescan' => 1, 'sesskey' => sesskey()]),
    'exporturl' => new moodle_url($PAGE->url, ['exportcsv' => 1, 'sesskey' => sesskey()]),
    'formurl' => new moodle_url('/local/storage_guard/dashboard.php'),
    'categoryselect' => html_writer::select(['0' => 'All Categories'] + $categories, 'category', $filter_category, false, ['class' => 'form-control mr-2']),
    'statusselect' => html_writer::select(['-1' => 'All', '0' => 'Active', '1' => 'Locked'], 'lockstatus', $filter_lock, false, ['class' => 'form-control mr-2']),
    'courses' => $courses_data
];

// 6. RENDER TEMPLATE
$dashboard = new \local_storage_guard\output\dashboard($renderdata);
echo $OUTPUT->render_from_template('local_storage_guard/dashboard', $dashboard->export_for_template($OUTPUT));

echo $OUTPUT->footer();