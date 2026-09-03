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

require_once(__DIR__ . '/../../config.php');

use local_unittours\local\tour_repository;

$courseid = required_param('id', PARAM_INT);
$tourid = required_param('tourid', PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);
$stepid = optional_param('stepid', 0, PARAM_INT);

$course = get_course($courseid);
$context = context_course::instance($course->id);

require_login($course);
require_capability('local/unittours:manage', $context);

$url = new moodle_url('/local/unittours/view.php', ['id' => $course->id, 'tourid' => $tourid]);
$tour = tour_repository::get_tour($tourid, $course->id);

$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_course($course);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(format_string($tour->name, true, ['context' => $context]));
$PAGE->set_heading($course->fullname);

if ($action === 'deletestep' && $stepid) {
    $step = tour_repository::get_step($stepid, $course->id);

    // Deletion only happens on a confirmed POST; the initial delete link is a safe GET
    // that renders this confirmation page.
    if (optional_param('confirm', 0, PARAM_BOOL) && data_submitted() && confirm_sesskey()) {
        tour_repository::delete_step($stepid, $course->id);
        redirect($url, get_string('stepdeleted', 'local_unittours'), null, \core\output\notification::NOTIFY_SUCCESS);
    }

    echo $OUTPUT->header();
    $continueurl = new moodle_url($url, [
        'action' => 'deletestep',
        'stepid' => $stepid,
        'confirm' => 1,
        'sesskey' => sesskey(),
    ]);
    echo $OUTPUT->confirm(
        get_string('deletestepconfirm', 'local_unittours', format_string($step->title, true, ['context' => $context])),
        new single_button($continueurl, get_string('delete'), 'post'),
        $url
    );
    echo $OUTPUT->footer();
    exit;
}

if (($action === 'movestepup' || $action === 'movestepdown') && $stepid && data_submitted() && confirm_sesskey()) {
    $direction = ($action === 'movestepup') ? 'up' : 'down';
    tour_repository::move_step($stepid, $course->id, $direction);
    redirect($url, get_string('steporderupdated', 'local_unittours'), null, \core\output\notification::NOTIFY_SUCCESS);
}

if ($action === 'resetmycompletion' && data_submitted() && confirm_sesskey()) {
    tour_repository::clear_completion($tour->id, $course->id, $USER->id);
    redirect($url, get_string('mycompletionreset', 'local_unittours'), null, \core\output\notification::NOTIFY_SUCCESS);
}

$steps = tour_repository::get_steps_for_tour($tour->id);
$renderer = $PAGE->get_renderer('local_unittours');

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($tour->name, true, ['context' => $context]));
echo $renderer->tour_page($tour, $steps, $course, $context);
echo $OUTPUT->footer();
