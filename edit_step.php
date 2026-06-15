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

use local_unittours\form\edit_step;
use local_unittours\local\target;
use local_unittours\local\tour_repository;

$courseid = required_param('id', PARAM_INT);
$tourid = required_param('tourid', PARAM_INT);
$stepid = optional_param('stepid', 0, PARAM_INT);
$pickedtargettype = optional_param('targettype', '', PARAM_ALPHANUMEXT);
$pickedtargetref = optional_param('targetref', '', PARAM_TEXT);
$pickedfallbackselector = optional_param('fallbackselector', '', PARAM_RAW);

$course = get_course($courseid);
$context = context_course::instance($course->id);
$tour = tour_repository::get_tour($tourid, $course->id);

require_login($course);
require_capability('local/unittours:manage', $context);

$urlparams = ['id' => $course->id, 'tourid' => $tour->id];
if ($stepid) {
    $urlparams['stepid'] = $stepid;
}
$url = new moodle_url('/local/unittours/edit_step.php', $urlparams);
$viewurl = new moodle_url('/local/unittours/view.php', ['id' => $course->id, 'tourid' => $tour->id]);

$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_course($course);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(get_string('editstep', 'local_unittours'));
$PAGE->set_heading($course->fullname);

$mform = new edit_step($url);

if ($stepid) {
    $step = tour_repository::get_step($stepid, $course->id);
    if ((int) $step->tourid !== (int) $tour->id) {
        throw new invalid_parameter_exception('Step does not belong to this tour.');
    }
    $step->id = $course->id;
    $step->stepid = $stepid;
    $step->content = [
        'text' => $step->content,
        'format' => $step->contentformat,
    ];
    if ($pickedtargettype) {
        $step->targettype = $pickedtargettype;
        $step->targetref = $pickedtargetref;
        $step->fallbackselector = $pickedfallbackselector;
    }
    $mform->set_data($step);
} else {
    $mform->set_data((object) [
        'id' => $course->id,
        'tourid' => $tour->id,
        'targettype' => $pickedtargettype ?: target::UNATTACHED,
        'targetref' => $pickedtargetref,
        'fallbackselector' => $pickedfallbackselector,
        'placement' => 'bottom',
        'showiftargetmissing' => 1,
        'backdrop' => 0,
        'audioenabled' => 0,
        'audioautoplay' => 0,
        'audiotext' => '',
        'audiolang' => '',
        'content' => [
            'text' => '',
            'format' => FORMAT_HTML,
        ],
    ]);
}

if ($mform->is_cancelled()) {
    redirect($viewurl);
}

if ($data = $mform->get_data()) {
    tour_repository::save_step($data, $course->id);
    redirect($viewurl, get_string('stepupdated', 'local_unittours'), null, \core\output\notification::NOTIFY_SUCCESS);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('editstep', 'local_unittours'));
$pickerparams = [
    'id' => $course->id,
    'unittours_pick' => 1,
    'tourid' => $tour->id,
    'sesskey' => sesskey(),
];
if ($stepid) {
    $pickerparams['stepid'] = $stepid;
}
$pickerurl = new moodle_url('/course/view.php', $pickerparams);
echo html_writer::div(
    html_writer::link($pickerurl, get_string('picktargetbutton', 'local_unittours'), ['class' => 'btn btn-secondary']),
    'mb-3'
);
$mform->display();
echo $OUTPUT->footer();
