<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

$observers = [
    [
        'eventname' => \core\event\course_deleted::class,
        'callback' => '\local_unittours\observer::course_deleted',
    ],
];
