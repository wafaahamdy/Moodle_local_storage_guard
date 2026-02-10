<?php
/**
 * Event registration for Storage Guard.
 * This file MUST ONLY contain the $observers array.
 */


defined('MOODLE_INTERNAL') || die();

$observers = [
    [
        'eventname'   => '\core\event\course_updated',
        'callback'    => 'local_storage_guard\observer::prevent_maxbytes_increase',
    ],
];