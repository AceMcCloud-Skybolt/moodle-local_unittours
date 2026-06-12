<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

use local_unittours\local\tour_repository;

function local_unittours_extend_navigation_course(navigation_node $navigation, stdClass $course, context_course $context): void {
    if (!has_capability('local/unittours:manage', $context)) {
        return;
    }

    $url = new moodle_url('/local/unittours/manage.php', ['id' => $course->id]);
    $navigation->add(
        get_string('unittours', 'local_unittours'),
        $url,
        navigation_node::TYPE_SETTING,
        null,
        'local_unittours',
        new pix_icon('i/settings', '')
    );
}

function local_unittours_before_footer(): void {
    global $COURSE, $PAGE, $USER;

    if (empty($COURSE->id) || (int) $COURSE->id === SITEID || !isloggedin() || isguestuser()) {
        return;
    }

    if ($PAGE->pagetype !== 'course-view-' . $COURSE->format) {
        return;
    }

    $context = context_course::instance($COURSE->id);
    $ispicker = optional_param('unittours_pick', 0, PARAM_BOOL);
    if ($ispicker && has_capability('local/unittours:manage', $context) && confirm_sesskey()) {
        $tourid = required_param('tourid', PARAM_INT);
        $stepid = optional_param('stepid', 0, PARAM_INT);
        $returnparams = [
            'id' => $COURSE->id,
            'tourid' => $tourid,
        ];
        if ($stepid) {
            $returnparams['stepid'] = $stepid;
        }

        $PAGE->requires->js_call_amd('local_unittours/target_picker', 'init', [[
            'returnurl' => (new moodle_url('/local/unittours/edit_step.php', $returnparams))->out(false),
            'strings' => [
                'picktarget' => get_string('picktarget', 'local_unittours'),
                'picktargetinstructions' => get_string('picktargetinstructions', 'local_unittours'),
                'cancel' => get_string('cancel'),
            ],
        ]]);
        return;
    }

    if (!has_capability('local/unittours:view', $context)) {
        return;
    }

    $canmanage = has_capability('local/unittours:manage', $context);
    $audience = $canmanage ? 'staff' : 'student';
    $enabledtours = tour_repository::get_enabled_tours_for_course((int) $COURSE->id, $audience, (int) $USER->id);
    if (!$enabledtours) {
        return;
    }
    $playabletours = tour_repository::get_playable_tours_for_course((int) $COURSE->id, $audience, (int) $USER->id);
    $autorunids = array_map(static fn($tour) => (int) $tour->id, array_values($playabletours));

    $payload = [
        'courseid' => (int) $COURSE->id,
        'userid' => (int) $USER->id,
        'sesskey' => sesskey(),
        'completeurl' => (new moodle_url('/local/unittours/complete.php'))->out(false),
        'reseturl' => (new moodle_url('/local/unittours/reset_completion.php'))->out(false),
        'canmanage' => $canmanage,
        'autorunids' => $autorunids,
        'tours' => [],
        'strings' => [
            'next' => get_string('next'),
            'back' => get_string('back'),
            'done' => get_string('done', 'local_unittours'),
            'skip' => get_string('skip', 'local_unittours'),
            'stepcounter' => get_string('stepcounter', 'local_unittours'),
            'playaudio' => get_string('playaudio', 'local_unittours'),
            'stopaudio' => get_string('stopaudio', 'local_unittours'),
            'audiounavailable' => get_string('audiounavailable', 'local_unittours'),
            'showtour' => get_string('showtour', 'local_unittours'),
            'resettourcompletion' => get_string('resettourcompletion', 'local_unittours'),
            'resettingshort' => get_string('resettingshort', 'local_unittours'),
        ],
    ];

    foreach ($enabledtours as $tour) {
        $steps = [];
        foreach (tour_repository::get_steps_for_tour((int) $tour->id) as $step) {
            $steps[] = [
                'id' => (int) $step->id,
                'title' => format_string($step->title, true, ['context' => $context]),
                'content' => format_text($step->content, $step->contentformat, ['context' => $context]),
                'targettype' => $step->targettype,
                'targetref' => $step->targetref,
                'fallbackselector' => $step->fallbackselector,
                'placement' => $step->placement,
                'showiftargetmissing' => (bool) $step->showiftargetmissing,
                'backdrop' => (bool) $step->backdrop,
                'audioenabled' => !empty($step->audioenabled),
                'audioautoplay' => !empty($step->audioautoplay),
                'audiotext' => (string) ($step->audiotext ?? ''),
                'audiolang' => (string) ($step->audiolang ?? ''),
            ];
        }

        if ($steps) {
            $payload['tours'][] = [
                'id' => (int) $tour->id,
                'name' => format_string($tour->name, true, ['context' => $context]),
                'showmode' => $tour->showmode,
                'steps' => $steps,
            ];
        }
    }

    if ($payload['tours']) {
        $PAGE->requires->js_call_amd('local_unittours/player', 'init', [$payload]);
    }
}
