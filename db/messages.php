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
 * This file tells Moodle that our plugin is a "Producer" of messages
 * 
 * @package     local_storage_guard
 * @copyright   2026 Wafaa Mansour <eng.wafaa.hamdy@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

$messageproviders = [
    'quota_warning' => [
        'capability' => 'local/storage_guard:receive_notifications', // Updated here
        'defaults' => [
            'popup' => MESSAGE_DEFAULT_ENABLED,
            'email' => MESSAGE_DISALLOWED,
            //MESSAGE_DEFAULT_DISABLED,   /// change to MESSAGE_DEFAULT_ENABLED,   to activate sending message by mail
        ],
    ],
];