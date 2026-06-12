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

$audience = has_capability('local/unittours:manage', $context) ? 'staff' : 'student';

if (!in_array($status, ['complete', 'skipped'], true)) {
    throw new invalid_parameter_exception('Invalid completion status.');
}

if (!tour_repository::can_user_access_tour($tourid, $course->id, $audience, $USER->id)) {
    throw new required_capability_exception($context, 'local/unittours:view', 'nopermissions', '');
}

tour_repository::mark_completion($tourid, $course->id, $USER->id, $status);

header('Content-Type: application/json');
echo json_encode(['status' => 'ok']);
