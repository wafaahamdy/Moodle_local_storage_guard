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
 * Plugin settings for admins to configure Storage Guard.
 *
 * @package     local_storage_guard
 * @copyright   2026 Wafaa Mansour <eng.wafaa.hamdy@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


if ($hassiteconfig) {
    $settings = new admin_settingpage('local_storage_guard', get_string('pluginname', 'local_storage_guard'));

    // Global Course Limit (Default 500MB).
    $settings->add(new admin_setting_configtext(
        'local_storage_guard/max_mbytes',
        get_string('max_mbytes', 'local_storage_guard'),
        get_string('max_mbytes_desc', 'local_storage_guard'),
        500, // 500MB in bytes
        PARAM_INT
    ));
    // Include Recycle Bin in Usage Calculations or not.
    $settings->add(new admin_setting_configcheckbox(
    'local_storage_guard/include_recycle_bin',
    get_string('includerecyclebin', 'local_storage_guard'),
    get_string('includerecyclebin_desc', 'local_storage_guard'),
    0 // Default is 0 (Off/Ignore).
    ));
    // Warning Threshold Percentage.
    $settings->add(new admin_setting_configtext(
        'local_storage_guard/warning_threshold',
        get_string('warning_threshold', 'local_storage_guard'),
        get_string('warning_threshold_desc', 'local_storage_guard'),
        80,
        PARAM_INT
    ));

    // --- WARNING SECTION ---
    $settings->add(new admin_setting_configcheckbox(
        'local_storage_guard/notify_warning',
        get_string('notify_warning', 'local_storage_guard'),
        '', 1
    ));


    // HTML Editor for the Warning Message
    $settings->add(new admin_setting_confightmleditor(
        'local_storage_guard/warning_message',
        get_string('warning_message', 'local_storage_guard'),
        get_string('message_help_vars', 'local_storage_guard'),
        '<p>Notice: The course <strong>{{coursename}}</strong> has used <strong>{{usage}}%</strong> of its storage.</p>',
        PARAM_RAW
    ));

    // --- RESTRICTED SECTION ---
    $settings->add(new admin_setting_configcheckbox(
        'local_storage_guard/notify_restricted',
        get_string('notify_restricted', 'local_storage_guard'),
        '', 1
    ));

    // HTML Editor for the Restricted Message.
    $settings->add(new admin_setting_confightmleditor(
        'local_storage_guard/restricted_message',
        get_string('restricted_message', 'local_storage_guard'),
        get_string('message_help_vars', 'local_storage_guard'),
        '<h3>Alert!</h3><p>Storage limit reached for <strong>{{coursename}}</strong>.</p>',
        PARAM_RAW
    ));
    $ADMIN->add('localplugins', $settings);
    // Add Dashboard Page Link.
    $ADMIN->add('localplugins', new admin_externalpage(
        'local_storage_guard_dashboard',
        get_string('dashboard', 'local_storage_guard'),
        new moodle_url('/local/storage_guard/dashboard.php'),
        'moodle/site:config'
    ));
}
