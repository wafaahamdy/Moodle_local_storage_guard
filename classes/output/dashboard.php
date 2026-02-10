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
 *  *
 * @package     local_storage_guard
 * @copyright   2026 Wafaa Mansour <eng.wafaa.hamdy@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


namespace local_storage_guard\output;

defined('MOODLE_INTERNAL') || die();

use renderable;
use templatable;
use renderer_base;
use stdClass;

class dashboard implements renderable, templatable {
    protected $data;

    public function __construct($data) {
        $this->data = $data;
    }

    public function export_for_template(renderer_base $output) {
        $export = new stdClass();
        $export->total_courses = $this->data['total_courses'] ?? 0;
        $export->total_locked = $this->data['total_locked'] ?? 0;
        $export->has_locked = ($export->total_locked > 0);
        
        $export->scanurl = $this->data['scanurl']->out(false);
        $export->exporturl = $this->data['exporturl']->out(false);
        $export->formurl = $this->data['formurl']->out(false);
        
        $export->categoryselect = $this->data['categoryselect'];
        $export->statusselect = $this->data['statusselect'];
        $export->courses = $this->data['courses'];
        
        return $export;
    }
}