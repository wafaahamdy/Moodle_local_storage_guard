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
 * Quota Enforcement Task.
 * @package     local_storage_guard
 * @copyright   2026 Wafaa Mansour <eng.wafaa.hamdy@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


namespace local_storage_guard\task;

use core\task\scheduled_task;
use core_user;
use context_course;
use moodle_url;
use stdClass;
/**
 * Class quota_enforcement_task
 * Logic: Checks Course Override -> Site Default.
 * Action: Locks course to 1MB and sends notifications.
 */
class quota_enforcement_task extends \core\task\scheduled_task {
    /**
     * Returns a descriptive name for this task.
     *
     * @return string
     */
    public function get_name() {
        return get_string('pluginname', 'local_storage_guard');
    }
    /**
     * The main execution method for the scheduled task.
     * It checks each course's storage usage against its effective quota and takes action accordingly.
     */
    public function execute() {
        global $DB, $CFG;

        mtrace("Starting Storage Guard maintenance task...");
        $manager = new \local_storage_guard\quota_manager();
        $locksizebytes = 1048576; // 1MB Hard limit. used for unlocking courses.
        $coursedefaultlimit = get_config('moodlecourse', 'maxbytes');
        // If the default isn't set, fallback to the absolute Site Max.
        if ($coursedefaultlimit === false || $coursedefaultlimit === null) {
            $coursedefaultlimit = $CFG->maxbytes;
        }

        // 1. Get all courses except the site home (ID 1).
        $courses = $DB->get_records_select('course', 'id > 1', [], '', 'id, fullname');

        foreach ($courses as $course) {
            $limitbytes = $manager->get_effective_quota_bytes($course->id);
            $usagemb = $manager->get_course_usage_mb($course->id);
            $usagebytes = $usagemb * 1048576;
            mtrace("Course: {$course->fullname} | Usage: {$usagemb}MB | Limit: " . ($limitbytes / 1048576) . "MB");

            $usagepct = ($limitbytes > 0) ? ($usagebytes / $limitbytes) * 100 : 0;
            $state = $DB->get_record('local_storage_guard', ['courseid' => $course->id]);

            mtrace("  -> Usage Percent: " . round($usagepct, 2) . "%");

            // SCENARIO A: Critical / Restricted (100% or more used).
            if ($usagepct >= 100) {
                if (!$state || $state->is_locked == 0) {
                    mtrace("LOCKING: {$course->fullname} ({$usagemb}MB / " . ($limitbytes / 1048576) . "MB)");
                    // 1. Apply 1MB Upload Lock.
                    $this->apply_maxbytes_lock($course->id, $locksizebytes);
                    // 2. Send Restricted Notification.
                    if (get_config('local_storage_guard', 'notify_restricted')) {
                        $this->notify_teachers($course, 'restricted', $usagepct);
                    }
                    $this->update_state($course->id, 100, 1);
                }
            } else { // SCENARIO B: Warning (e.g., 80% reached).
                if ($usagepct >= 80) {
                    // Only notify if we haven't sent a warning for this level yet.
                    if (!$state || $state->is_locked == 0) {
                        if (get_config('local_storage_guard', 'notify_warning')) {
                            $this->notify_teachers($course, 'warning', $usagepct);
                        }
                        $this->update_state($course->id, 80, 0);
                    }
                }
                // SCENARIO C: Under Limit (Auto-Unlock if limit increased or files deleted).
                if ($state && $state->is_locked == 1) {
                    mtrace(" > UNLOCKING: {$course->fullname} is now below 100% limit.");
                    // Restore the course maxbytes to the default limit.
                    $DB->set_field('course', 'maxbytes', $coursedefaultlimit, ['id' => $course->id]);
                    \cache_helper::purge_by_event('changesincourse');
                    // Update our local table so we know it's unlocked.
                    $this->update_state($course->id, 0, 0);
                }
            }
        }
        mtrace("Storage Guard maintenance task completed.");
    }

    /**
     * Forces the course maxbytes setting to 1MB to stop new uploads.
     */
    private function apply_maxbytes_lock(int $courseid, int $locksize): void {
        global $DB;
        $DB->set_field('course', 'maxbytes', $locksize, ['id' => $courseid]);
        mtrace("  -> Applied maxbytes lock of {$locksize} bytes.");
        // Purge cache so the file picker reflects the 1MB limit immediately.
        \cache_helper::purge_by_event('changesincourse');
    }

    /**
     * Sends the HTML email/notification using the templates in settings.
     */
    private function notify_teachers($course, string $type, float $usagepct): void {
        $configkey = ($type === 'restricted') ? 'restricted_message' : 'warning_message';
        $htmltemplate = get_config('local_storage_guard', $configkey);
        if (empty($htmltemplate)) {
            return;
        }
        $variables = [
            '{{coursename}}' => $course->fullname,
            '{{limit}}'      => round(get_config('local_storage_guard', 'max_mbytes'), 0),
            '{{usage}}'      => round($usagepct, 1),
            '{{courseurl}}'  => (new \moodle_url('/course/view.php', ['id' => $course->id]))->out(false),
        ];
        $processedhtml = str_replace(array_keys($variables), array_values($variables), $htmltemplate);
        $context = \context_course::instance($course->id);
        // Uses the capability we created to find who should receive alerts.
        $teachers = get_enrolled_users($context, 'local/storage_guard:receive_notifications');
        foreach ($teachers as $teacher) {
            // Use the modern message class.
            $eventdata = new \core\message\message();
            $eventdata->component         = 'local_storage_guard';
            $eventdata->name              = 'quota_warning';
            $eventdata->userfrom          = \core_user::get_noreply_user();
            $eventdata->userto            = $teacher;
            $eventdata->subject           = get_string('pluginname', 'local_storage_guard');
            $eventdata->fullmessage       = html_to_text($processedhtml);
            $eventdata->fullmessageformat = FORMAT_HTML;
            $eventdata->fullmessagehtml   = $processedhtml;
            $eventdata->smallmessage      = strip_tags($processedhtml);
            $eventdata->notification      = 1;
            $eventdata->component = 'moodle';
            $eventdata->name = 'instantmessage';
            $eventdata->courseid          = $course->id;
            // Use the global function - it handles the argument mapping automatically
            try {
                message_send($eventdata);
                mtrace("  -> Notification sent to {$teacher->email}.");
            } catch (\Exception $e) {
                // This catches the XAMPP mail error so the task doesn't fail.
                mtrace("Notice: Notification recorded in Bell icon, but email failed (Normal in XAMPP).");
            }
        }
    }

    /**
     * Syncs the status with our tracking table.
     */
    private function update_state(int $courseid, int $pct, int $locked): void {
        global $DB;
        $record = $DB->get_record('local_storage_guard', ['courseid' => $courseid]);
        $data = new \stdClass();
        $data->courseid = $courseid;
        $data->is_locked = $locked;
        $data->last_notified = time();
        if (!$record) {
            $DB->insert_record('local_storage_guard', $data);
        } else {
            $data->id = $record->id;
            $DB->update_record('local_storage_guard', $data);
        }
    }
}
