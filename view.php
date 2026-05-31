<?php
// This file is part of Moodle - http://moodle.org/

require_once(__DIR__ . '/../../config.php');

use local_unittours\local\tour_repository;
use local_unittours\local\target_resolver;

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

if ($action === 'deletestep' && $stepid && confirm_sesskey()) {
    tour_repository::delete_step($stepid, $course->id);
    redirect($url, get_string('stepdeleted', 'local_unittours'), null, \core\output\notification::NOTIFY_SUCCESS);
}

if (($action === 'movestepup' || $action === 'movestepdown') && $stepid && confirm_sesskey()) {
    $direction = ($action === 'movestepup') ? 'up' : 'down';
    tour_repository::move_step($stepid, $course->id, $direction);
    redirect($url, get_string('steporderupdated', 'local_unittours'), null, \core\output\notification::NOTIFY_SUCCESS);
}

if ($action === 'resetmycompletion' && confirm_sesskey()) {
    tour_repository::clear_completion($tour->id, $course->id, $USER->id);
    redirect($url, get_string('mycompletionreset', 'local_unittours'), null, \core\output\notification::NOTIFY_SUCCESS);
}

$steps = tour_repository::get_steps_for_tour($tour->id);

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($tour->name, true, ['context' => $context]));

$buttons = [
    html_writer::link(
        new moodle_url('/local/unittours/edit_tour.php', ['id' => $course->id, 'tourid' => $tour->id]),
        get_string('edittour', 'local_unittours'),
        ['class' => 'btn btn-secondary']
    ),
    html_writer::link(
        new moodle_url('/local/unittours/edit_step.php', ['id' => $course->id, 'tourid' => $tour->id]),
        get_string('addstep', 'local_unittours'),
        ['class' => 'btn btn-primary']
    ),
    html_writer::link(
        new moodle_url($url, ['action' => 'resetmycompletion', 'sesskey' => sesskey()]),
        get_string('resetmycompletion', 'local_unittours'),
        ['class' => 'btn btn-secondary']
    ),
];
echo html_writer::div(implode(' ', $buttons), 'mb-3');

if (!empty($tour->description)) {
    echo $OUTPUT->box(format_text($tour->description, $tour->descriptionformat, ['context' => $context]));
}

if (empty($steps)) {
    echo $OUTPUT->notification(get_string('nosteps', 'local_unittours'), \core\output\notification::NOTIFY_INFO);
    echo $OUTPUT->footer();
    exit;
}

$table = new html_table();
$table->head = [
    get_string('steptitle', 'local_unittours'),
    get_string('targettype', 'local_unittours'),
    get_string('targetlabel', 'local_unittours'),
    get_string('targethealth', 'local_unittours'),
    get_string('audiostatus', 'local_unittours'),
    get_string('placement', 'local_unittours'),
    get_string('actions'),
];

foreach ($steps as $step) {
    $editurl = new moodle_url('/local/unittours/edit_step.php', [
        'id' => $course->id,
        'tourid' => $tour->id,
        'stepid' => $step->id,
    ]);
    $deleteurl = new moodle_url($url, [
        'action' => 'deletestep',
        'stepid' => $step->id,
        'sesskey' => sesskey(),
    ]);
    $moveupurl = new moodle_url($url, [
        'action' => 'movestepup',
        'stepid' => $step->id,
        'sesskey' => sesskey(),
    ]);
    $movedownurl = new moodle_url($url, [
        'action' => 'movestepdown',
        'stepid' => $step->id,
        'sesskey' => sesskey(),
    ]);

    $targetinfo = target_resolver::describe($step, $course);
    if ($targetinfo->found === true) {
        $health = html_writer::span(get_string('targetfound', 'local_unittours'), 'badge badge-success');
    } else if ($targetinfo->found === false) {
        $health = html_writer::span(get_string('targetneedsattention', 'local_unittours'), 'badge badge-danger');
    } else {
        $health = html_writer::span(get_string('targetunchecked', 'local_unittours'), 'badge badge-secondary');
    }
    if (!empty($targetinfo->detail)) {
        $health .= html_writer::div($targetinfo->detail, 'small text-muted mt-1');
    }

    if (empty($step->audioenabled)) {
        $audiostatus = html_writer::span(get_string('audiooff', 'local_unittours'), 'badge badge-secondary');
    } else if (trim((string)($step->audiotext ?? '')) === '') {
        $audiostatus = html_writer::span(get_string('audioneedstext', 'local_unittours'), 'badge badge-warning');
    } else {
        $audiolabel = get_string('audioonbrowserdependent', 'local_unittours');
        if (!empty($step->audiolang)) {
            $audiolabel .= ' (' . s($step->audiolang) . ')';
        }
        $audiostatus = html_writer::span($audiolabel, 'badge badge-info');
    }

    $table->data[] = [
        format_string($step->title, true, ['context' => $context]),
        get_string('target_' . $step->targettype, 'local_unittours'),
        s($targetinfo->label),
        $health,
        $audiostatus,
        get_string('placement_' . $step->placement, 'local_unittours'),
        html_writer::link($moveupurl, get_string('moveup', 'local_unittours')) . ' | ' .
            html_writer::link($movedownurl, get_string('movedown', 'local_unittours')) . ' | ' .
            html_writer::link($editurl, get_string('edit')) . ' | ' .
            html_writer::link($deleteurl, get_string('delete')),
    ];
}

echo html_writer::table($table);
echo $OUTPUT->footer();
