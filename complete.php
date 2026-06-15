<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Unit tours plugin.
 *
 * @package    local_unittours
 * @copyright  2026 Murdoch University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

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
