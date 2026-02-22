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
 * Logic: Checks Course Override -> Site Default.
 * Action: Locks course to 1MB and sends notifications.
 * @package     local_storage_guard
 * @copyright   2026 Wafaa Mansour <eng.wafaa.hamdy@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


namespace local_storage_guard\task;

defined('MOODLE_INTERNAL') || die();

use core\task\scheduled_task;
use core_user;
use context_course;
use moodle_url;
use stdClass;

class quota_enforcement_task extends \core\task\scheduled_task {

    public function get_name() {
        return get_string('pluginname', 'local_storage_guard');
    }

    public function execute() {
        global $DB, $CFG;

        mtrace("Starting Storage Guard maintenance task...");
        
        $manager = new \local_storage_guard\quota_manager();
        $lock_size_bytes = 1048576; // 1MB Hard limit.
        
        //used for unlocking courses
        $course_default_limit = get_config('moodlecourse', 'maxbytes');
        //  If the default isn't set, fallback to the absolute Site Max
        if ($course_default_limit === false || $course_default_limit === null) {
          $course_default_limit = $CFG->maxbytes;
        }

        // 1. Get all courses except the site home (ID 1).
        $courses = $DB->get_records_select('course', 'id > 1', [], '', 'id, fullname');

        foreach ($courses as $course) {
            $limit_bytes = $manager->get_effective_quota_bytes($course->id);
            $usage_mb = $manager->get_course_usage_mb($course->id);
            $usage_bytes = $usage_mb * 1048576;
            mtrace("Course: {$course->fullname} | Usage: {$usage_mb}MB | Limit: " . ($limit_bytes/1048576) . "MB");

            $usage_pct = ($limit_bytes > 0) ? ($usage_bytes / $limit_bytes) * 100 : 0;
            $state = $DB->get_record('local_storage_guard', ['courseid' => $course->id]);

            mtrace("  -> Usage Percent: " . round($usage_pct, 2) . "%");

            // SCENARIO A: Critical / Restricted (100% or more used).
            if ($usage_pct >= 100) {
                if (!$state || $state->is_locked == 0) {
                    mtrace("LOCKING: {$course->fullname} ({$usage_mb}MB / " . ($limit_bytes/1048576) . "MB)");
                    
                    // 1. Apply 1MB Upload Lock.
                    $this->apply_maxbytes_lock($course->id, $lock_size_bytes);
                    
                    // 2. Send Restricted Notification.
                    if (get_config('local_storage_guard', 'notify_restricted')) {
                        $this->notify_teachers($course, 'restricted', $usage_pct);
                    }

                    $this->update_state($course->id, 100, 1);
                }
            } 
            // SCENARIO B: Warning (e.g., 80% reached).
            else { if ($usage_pct >= 80) {
                // Only notify if we haven't sent a warning for this level yet.
                if (!$state || $state->is_locked == 0) {
                    if (get_config('local_storage_guard', 'notify_warning')) {
                        $this->notify_teachers($course, 'warning', $usage_pct);
                    }
                    $this->update_state($course->id, 80, 0);
                }
            }
            // SCENARIO C: Under Limit (Auto-Unlock if limit increased or files deleted).
            if ($state && $state->is_locked == 1) {
            mtrace(" > UNLOCKING: {$course->fullname} is now below 100% limit.");

            // Restore the course maxbytes to the default limit.
           $DB->set_field('course', 'maxbytes', $course_default_limit, ['id' => $course->id]);
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
    private function notify_teachers($course, string $type, float $usage_pct): void {
        $config_key = ($type === 'restricted') ? 'restricted_message' : 'warning_message';
        $html_template = get_config('local_storage_guard', $config_key);
        
        if (empty($html_template)) return;

        $variables = [
            '{{coursename}}' => $course->fullname,
            '{{limit}}'      => round(get_config('local_storage_guard', 'max_mbytes'), 0),
            '{{usage}}'      => round($usage_pct, 1),
            '{{courseurl}}'  => (new \moodle_url('/course/view.php', ['id' => $course->id]))->out(false),
        ];
        $processed_html = str_replace(array_keys($variables), array_values($variables), $html_template);

        $context = \context_course::instance($course->id);
        // Uses the capability we created to find who should receive alerts.
        $teachers = get_enrolled_users($context, 'local/storage_guard:receive_notifications');

        foreach ($teachers as $teacher) {
           // Use the modern message class
        $eventdata = new \core\message\message();
        $eventdata->component         = 'local_storage_guard';
        $eventdata->name              = 'quota_warning';
        $eventdata->userfrom          = \core_user::get_noreply_user();
        $eventdata->userto            = $teacher;
        $eventdata->subject           = get_string('pluginname', 'local_storage_guard');
        $eventdata->fullmessage       = html_to_text($processed_html);
        $eventdata->fullmessageformat = FORMAT_HTML;
        $eventdata->fullmessagehtml   = $processed_html;
        $eventdata->smallmessage      = strip_tags($processed_html);
        $eventdata->notification      = 1;
        // these two lines to bypass the email processor for this specific test
        $eventdata->component = 'moodle'; 
        $eventdata->name = 'instantmessage';
        // end of mail bypass
        $eventdata->courseid          = $course->id;

        // Use the global function - it handles the argument mapping automatically
       // message_send($eventdata);
       try {
        message_send($eventdata);
        mtrace("  -> Notification sent to {$teacher->email}.");
       } catch (\Exception $e) {
        // This catches the XAMPP mail error so the task doesn't fail
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