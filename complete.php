<?php
// This file is part of Moodle - http://moodle.org/

define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../config.php');

use local_unittours\local\tour_repository;

$courseid = required_param('courseid', PARAM_INT);
$tourid = required_param('tourid', PARAM_INT);
$status = required_param('status', PARAM_ALPHA);

require_sesskey();

$course = get_course($courseid);
$context = context_course::instance($course->id);

require_login($course);
require_capability('local/unittours:view', $context);

if (!in_array($status, ['complete', 'skipped'], true)) {
    throw new invalid_parameter_exception('Invalid completion status.');
}

tour_repository::mark_completion($tourid, $course->id, $USER->id, $status);

header('Content-Type: application/json');
echo json_encode(['status' => 'ok']);
