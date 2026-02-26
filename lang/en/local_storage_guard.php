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
 * Plugin strings are defined here.
 *
 * @package     local_storage_guard
 * @category    string
 * @copyright   2026 Wafaa Mansour <eng.wafaa.hamdy@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Storage Guard';
$string['local_storage_guard:manage'] = 'Manage storage limits';
$string['storage_guard:receive_notifications'] = 'Receive Storage Notifications';
$string['storage_guard:manage_quota'] = 'Manage Course Storage Quota';
$string['custom_quota_mb'] = 'Course Quota Override (MB)';
$string['storage_settings'] = 'Storage Guard Settings';

$string['max_mbytes'] = 'Maximum Course Storage Size (in MB)';
$string['max_mbytes_desc'] = 'Set the maximum allowed storage size for each course in megabytes (MB).';
$string['warning_threshold'] = 'Warning Threshold Percentage';
$string['notify_warning'] = 'Enable Storage Notifications';
$string['warning_threshold_desc'] = 'Set the percentage of storage usage at which a warning will be issued to course creators and teachers.';
$string['notify_warning_desc'] = 'If enabled, course creators and teachers will receive notifications when their course storage exceeds the warning threshold.';
$string['message_help_vars'] = 'You can use the following placeholders in your message: {{coursename}} - Name of the course, {{usage}} - Current storage usage percentage.';
$string['warning_message'] = 'Storage Warning Message';
$string['notify_restricted'] = 'Enable Storage Restriction Notifications';
$string['notify_restricted_desc'] = 'If enabled, course creators and teachers will receive notifications when their course storage exceeds the maximum limit and restrictions are applied.';
$string['restricted_message'] = 'Storage Restriction Message';
$string['restricted_message_help_vars'] = 'You can use the following placeholders in your message: {{coursename}} - Name of the course, {{maxsize}} - Maximum allowed storage size in MB.';

$string['category_quota_mb'] = 'Category Quota Default (MB)';
$string['includerecyclebin'] = 'Include Recycle Bin in Usage Calculations';
$string['includerecyclebin_desc'] = 'If enabled, files in the Recycle Bin will be included in storage usage calculations. If disabled, they will be ignored';

$string['quota_exceeded'] = 'Upload Blocked: This course has exceeded its allowed quota of {$a}. Please delete old files or contact site administrator.';
$string['usage_updated'] = 'Storage usage record updated.';
$string['error_maxbytes_locked'] = 'Quota Lock: You cannot increase the Maximum Upload Limit while this course is over its storage quota. Please delete files first.';

$string['dashboard'] = 'Storage Guard Dashboard';
$string['course_storage_overview'] = 'Course Storage Overview';
$string['run_scan_now'] = 'Run Storage Scan Now';
$string['scan_complete'] = 'Storage scan completed successfully. All course limits have been updated.';
