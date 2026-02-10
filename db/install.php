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


defined('MOODLE_INTERNAL') || die();

function xmldb_local_storage_guard_install() {
    global $DB;

    $field_configs = [
        ['area' => 'course', 'shortname' => 'custom_quota_mb', 'name' => 'Course Quota Override (MB)'],
       
    ];

    foreach ($field_configs as $cfg) {
        $handler = \core_customfield\handler::get_handler('core_course', $cfg['area']);
        if (!$handler) continue;

        $category_name = 'Storage Guard Settings';
        $category_rec = $DB->get_record('customfield_category', [
            'component' => 'core_course', 
            'area'      => $cfg['area'], 
            'name'      => $category_name
        ]);
        
        if (!$category_rec) {
            $new_cat = $handler->create_category($category_name);
            // Fix for "get() on int" error:
            $category_id = is_object($new_cat) ? $new_cat->get('id') : $new_cat;
        } else {
            $category_id = $category_rec->id;
        }

        // Fix for "timecreated" error: Added timestamps and required fields.
        if (!$DB->record_exists('customfield_field', ['shortname' => $cfg['shortname'], 'categoryid' => $category_id])) {
            $now = time();
            $field_data = (object)[
                'categoryid'   => $category_id,
                'type'         => 'number',
                'shortname'    => $cfg['shortname'],
                'name'         => $cfg['name'],
                'description'  => '',
                'descriptionformat' => FORMAT_HTML,
                'sortorder'    => 0,
                'configdata'   => json_encode([
                    'visibility' => 2, 
                    'display' => 1,
                    'locked'     => 1, // Field is locked for non-admins
                    ]),
                'timecreated'  => $now,
                'timemodified' => $now
            ];
            $DB->insert_record('customfield_field', $field_data);
        }
    }
}