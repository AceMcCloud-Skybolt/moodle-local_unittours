<?php
// This file is part of Moodle - http://moodle.org/

define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../config.php');

use local_unittours\local\tour_repository;

$courseid = required_param('courseid', PARAM_INT);
$tourid = optional_param('tourid', 0, PARAM_INT);

require_sesskey();

$course = get_course($courseid);
$context = context_course::instance($course->id);

require_login($course);
require_capability('local/unittours:view', $context);

if ($tourid) {
    tour_repository::clear_completion($tourid, $course->id, $USER->id);
} else {
    tour_repository::clear_completion_for_course($course->id, $USER->id);
}

header('Content-Type: application/json');
echo json_encode(['status' => 'ok']);
