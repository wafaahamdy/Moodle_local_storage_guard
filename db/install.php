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
 * Post-installation script for Storage Guard.
 * Creates the cascading custom fields for Courses.
 *
 * @package     local_storage_guard
 * @copyright   2026 Wafaa Mansour <eng.wafaa.hamdy@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * This function is automatically called by Moodle after the plugin is installed.
 * It creates a custom field category and a number field for course quota overrides.
 */
function xmldb_local_storage_guard_install() {
    global $DB;

    $fieldconfigs = [
        ['area' => 'course', 'shortname' => 'custom_quota_mb', 'name' => 'Course Quota Override (MB)'],
    ];

    foreach ($fieldconfigs as $cfg) {
        $handler = \core_customfield\handler::get_handler('core_course', $cfg['area']);
        if (!$handler) {
            continue;
        }

        $categoryname = 'Storage Guard Settings';
        $categoryrec = $DB->get_record('customfield_category', [
            'component' => 'core_course',
            'area'      => $cfg['area'],
            'name'      => $categoryname,
        ]);
        if (!$categoryrec) {
            $newcat = $handler->create_category($categoryname);
            // Fix for "get() on int" error.
            $categoryid = is_object($newcat) ? $newcat->get('id') : $newcat;
        } else {
            $categoryid = $categoryrec->id;
        }

        // Fix for "timecreated" error: Added timestamps and required fields.
        if (!$DB->record_exists('customfield_field', ['shortname' => $cfg['shortname'], 'categoryid' => $categoryid])) {
            $now = time();
            $fielddata = (object)[
                'categoryid'   => $categoryid,
                'type'         => 'number',
                'shortname'    => $cfg['shortname'],
                'name'         => $cfg['name'],
                'description'  => '',
                'descriptionformat' => FORMAT_HTML,
                'sortorder'    => 0,
                'configdata'   => json_encode([
                    'visibility' => 2,
                    'display' => 1,
                    'locked'     => 1, // Field is locked for non-admins.
                    ]),
                'timecreated'  => $now,
                'timemodified' => $now,
            ];
            $DB->insert_record('customfield_field', $fielddata);
        }
    }
}
