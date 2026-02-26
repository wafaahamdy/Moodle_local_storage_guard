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

namespace local_storage_guard;

use moodle_url;
use core\notification;
/**
 * Event observers for Storage Guard.
 *
 * @package     local_storage_guard
 * @copyright   2026 Wafaa Mansour <eng.wafaa.hamdy@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observer {

    /**
     * Strictly prevents ANY change to the Maximum Upload limit if the course is locked.
     */
    public static function prevent_maxbytes_increase(\core\event\course_updated $event) {
        global $DB, $USER;

        // 1. Always allow Site Admins to override for emergencies.
        if (is_siteadmin()) {
            return;
        }

        $eventdata = $event->get_data();
        $courseid  = $eventdata['courseid'];

        // 2. Verify the Lock Status in our custom table.
        $state = $DB->get_record('local_storage_guard', ['courseid' => $courseid]);
        // If the course isn't locked in our plugin, we don't interfere.
        if (!$state || $state->is_locked == 0) {
            return;
        }

        // 3. Check what the 'maxbytes' value is AFTER the teacher tried to save it.
        $actualcurrentlimit = (int)$DB->get_field('course', 'maxbytes', ['id' => $courseid]);
        $locksize = 1048576; // 1MB

        // 4. If it is NOT 1MB, the teacher tried to change it.
        if ($actualcurrentlimit !== $locksize) {
            // Force it back to 1MB in the database immediately.
            $DB->set_field('course', 'maxbytes', $locksize, ['id' => $courseid]);
            // Clear the course cache so the UI reflects the 1MB limit immediately.
            \cache_helper::purge_by_event('changesincourse');
            // Queue the "Popup" error message.
            notification::error(get_string('error_maxbytes_locked', 'local_storage_guard'));
            // Redirect back to the edit page. This stops the "Changes Saved" message
            // from appearing and shows our error instead.
            $url = new moodle_url('/course/edit.php', ['id' => $courseid]);
            redirect($url);
        }
    }
}
