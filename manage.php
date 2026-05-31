<?php
// This file is part of Moodle - http://moodle.org/

require_once(__DIR__ . '/../../config.php');

use local_unittours\local\tour_repository;

$courseid = required_param('id', PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);
$tourid = optional_param('tourid', 0, PARAM_INT);

$course = get_course($courseid);
$context = context_course::instance($course->id);

require_login($course);
require_capability('local/unittours:manage', $context);

$url = new moodle_url('/local/unittours/manage.php', ['id' => $course->id]);
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_course($course);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(get_string('manageunittours', 'local_unittours'));
$PAGE->set_heading($course->fullname);

if ($action === 'createtour' && confirm_sesskey()) {
    $newtourid = tour_repository::create_draft_tour($course->id);
    redirect(
        new moodle_url('/local/unittours/view.php', ['id' => $course->id, 'tourid' => $newtourid]),
        get_string('tourcreated', 'local_unittours'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

if ($action === 'deletetour' && $tourid && confirm_sesskey()) {
    tour_repository::delete_tour($tourid, $course->id);
    redirect($url, get_string('tourdeleted', 'local_unittours'), null, \core\output\notification::NOTIFY_SUCCESS);
}

$tours = tour_repository::get_tours_for_course($course->id);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('manageunittours', 'local_unittours'));

$createurl = new moodle_url($url, ['action' => 'createtour', 'sesskey' => sesskey()]);
echo $OUTPUT->single_button($createurl, get_string('createtour', 'local_unittours'), 'post');

if (empty($tours)) {
    echo $OUTPUT->notification(get_string('notours', 'local_unittours'), \core\output\notification::NOTIFY_INFO);
    echo $OUTPUT->footer();
    exit;
}

$table = new html_table();
$table->head = [
    get_string('tourname', 'local_unittours'),
    get_string('status', 'local_unittours'),
    get_string('steps', 'local_unittours'),
    get_string('actions'),
];

foreach ($tours as $tour) {
    $viewurl = new moodle_url('/local/unittours/view.php', ['id' => $course->id, 'tourid' => $tour->id]);
    $editurl = new moodle_url('/local/unittours/edit_tour.php', ['id' => $course->id, 'tourid' => $tour->id]);
    $deleteurl = new moodle_url($url, [
        'action' => 'deletetour',
        'tourid' => $tour->id,
        'sesskey' => sesskey(),
    ]);

    $table->data[] = [
        html_writer::link($viewurl, format_string($tour->name, true, ['context' => $context])),
        $tour->enabled ? get_string('enabled', 'local_unittours') : get_string('disabled', 'local_unittours'),
        tour_repository::count_steps((int) $tour->id),
        html_writer::link($editurl, get_string('edit')) . ' | ' .
            html_writer::link($deleteurl, get_string('delete')),
    ];
}

echo html_writer::table($table);
echo $OUTPUT->footer();
