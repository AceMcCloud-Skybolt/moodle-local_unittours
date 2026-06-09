<?php
// This file is part of Moodle - http://moodle.org/

namespace local_unittours;

defined('MOODLE_INTERNAL') || die();

class observer {

    public static function course_deleted(\core\event\course_deleted $event): void {
        \local_unittours\local\tour_repository::delete_course_data((int) $event->objectid);
    }
}
