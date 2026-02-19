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
 * Logic handler for calculating cascading storage quotas.
 *
 * @package     local_storage_guard
 * @copyright   2026 Wafaa Mansour <eng.wafaa.hamdy@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_storage_guard;

defined('MOODLE_INTERNAL') || die();

/**
 * Class quota_manager
 * * Manages retrieval of effective quotas and current course usage.
 */
class quota_manager {

    /**
     * Calculates the effective quota for a course in Megabytes (MB).
     * * @param int $courseid The ID of the course to check.
     * @return int The quota in MB.
     */
    public function get_effective_quota($courseid) {
        // 1. Check for a Course-level override in custom fields.
        $course_override = $this->get_course_custom_field_value($courseid, 'custom_quota_mb');

        if (!empty($course_override) && is_numeric($course_override) && (int)$course_override > 0) {
            return (int)$course_override;
        }

        // 2. Fallback to Site Default (max_mbytes) set in plugin settings.
        $site_default = get_config('local_storage_guard', 'max_mbytes');
        
        // Returns site default, or 1000MB (1GB) as a last resort if nothing is configured.
        return ($site_default !== false && $site_default !== '') ? (int)$site_default : 1000;
    }

    /**
     * Returns the effective quota converted to Bytes.
     * * @param int $courseid
     * @return int Quota in Bytes.
     */
    public function get_effective_quota_bytes($courseid) {
        $quota_mb = $this->get_effective_quota($courseid);
        
        // Convert MB to Bytes (1 MB = 1024 * 1024 bytes).
        return $quota_mb * 1048576;
    }

    /**
     * Retrieves the current total file usage of a course in Megabytes (MB).
     * * This function leverages the 'report_coursesize' plugin library to ensure 
     * consistency with the system's Course Size report.
     *
     * @param int $courseid The ID of the course to measure.
     * @return float The total size in MB, rounded to two decimal places.
     */
    public function get_course_usage_mb($courseid) {
        global $CFG, $DB;

        // Path to the Course Size report library.
        /* Ignore course size report
        $libfile = $CFG->dirroot . '/report/coursesize/lib.php';
              
        if (file_exists($libfile)) {
            require_once($libfile);
            
            // This function calculates size across all course contexts.
            $bytes = report_coursesize_get_course_size($courseid);
            
            return round(($bytes ?: 0) / 1048576, 2);
        }*/

        // Fallback: Manual calculation if report_coursesize is missing.
        $context = \context_course::instance($courseid);
        $sql = "SELECT SUM(f.filesize)
          FROM {files} f
          JOIN {context} ctx ON f.contextid = ctx.id
          WHERE (ctx.id = :contextid OR ctx.path LIKE :path)
          AND f.filename != '.' -- Ignore directory markers
          AND f.component != 'user'  -- Ignore student/teacher personal private files
          AND f.component != 'tool_recyclebin'  -- Ignore items sitting in the Recycle Bin
          AND f.filearea  != 'recyclebin'
          AND f.filearea  != 'trashcan' -- Ignore the 4-day safety trash area
          AND f.filearea  != 'draft'"; // Ignore files currently being uploaded (Drafts)
         
        $bytes = $DB->get_field_sql($sql, [
            'contextid' => $context->id,
            'path'      => $context->path . '/%'
        ]);
        
        return round(($bytes ?: 0) / 1048576, 2);
    }

    /**
     * Internal helper to fetch custom field values directly from the DB.
     * * @param int $courseid
     * @param string $shortname
     * @return mixed
     */
    protected function get_course_custom_field_value($courseid, $shortname) {
        global $DB;

        $sql = "SELECT d.intvalue, d.value
                  FROM {customfield_data} d
                  JOIN {customfield_field} f ON d.fieldid = f.id
                 WHERE d.instanceid = :courseid
                   AND f.shortname = :shortname";

        $params = ['courseid' => $courseid, 'shortname' => $shortname];
        $record = $DB->get_record_sql($sql, $params);

        if ($record) {
            // Moodle 'number' fields store values in intvalue.
            return !empty($record->intvalue) ? $record->intvalue : $record->value;
        }

        return null;
    }
}