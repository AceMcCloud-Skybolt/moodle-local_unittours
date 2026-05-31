<?php
// This file is part of Moodle - http://moodle.org/

require_once(__DIR__ . '/../../config.php');

use local_unittours\form\edit_tour;
use local_unittours\local\tour_repository;

$courseid = required_param('id', PARAM_INT);
$tourid = optional_param('tourid', 0, PARAM_INT);

$course = get_course($courseid);
$context = context_course::instance($course->id);

require_login($course);
require_capability('local/unittours:manage', $context);

$urlparams = ['id' => $course->id];
if ($tourid) {
    $urlparams['tourid'] = $tourid;
}
$url = new moodle_url('/local/unittours/edit_tour.php', $urlparams);
$manageurl = new moodle_url('/local/unittours/manage.php', ['id' => $course->id]);

$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_course($course);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(get_string('edittour', 'local_unittours'));
$PAGE->set_heading($course->fullname);

$customdata = ['courseid' => $course->id];
$mform = new edit_tour($url, $customdata);

if ($tourid) {
    $tour = tour_repository::get_tour($tourid, $course->id);
    $tour->id = $course->id;
    $tour->tourid = $tourid;
    $tour->description = [
        'text' => $tour->description,
        'format' => $tour->descriptionformat,
    ];
    $mform->set_data($tour);
} else {
    $mform->set_data((object) [
        'id' => $course->id,
        'enabled' => 0,
        'audience' => 'student',
        'showmode' => 'untilcomplete',
        'description' => [
            'text' => '',
            'format' => FORMAT_HTML,
        ],
    ]);
}

if ($mform->is_cancelled()) {
    redirect($manageurl);
}

if ($data = $mform->get_data()) {
    $savedtourid = tour_repository::save_tour($data, $course->id);
    redirect(
        new moodle_url('/local/unittours/view.php', ['id' => $course->id, 'tourid' => $savedtourid]),
        get_string('tourupdated', 'local_unittours'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('edittour', 'local_unittours'));
$mform->display();
echo $OUTPUT->footer();
