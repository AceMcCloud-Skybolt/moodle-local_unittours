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

namespace local_unittours\hook\output;

use local_unittours\local\tour_repository;

/**
 * Hook callback that injects the tour player (or target picker) on course pages.
 *
 * @package    local_unittours
 * @copyright  2026 Murdoch University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class before_footer_html_generation {
    /**
     * Initialise the tour player or the editor target picker on course view pages.
     *
     * @param \core\hook\output\before_footer_html_generation $hook The hook instance.
     */
    public static function callback(\core\hook\output\before_footer_html_generation $hook): void {
        global $COURSE, $PAGE, $USER;

        if (empty($COURSE->id) || (int) $COURSE->id === SITEID || !isloggedin() || isguestuser()) {
            return;
        }

        // Match any course view page regardless of course format; custom formats may
        // register pagetypes beyond 'course-view-<format>'.
        if (strpos($PAGE->pagetype, 'course-view') !== 0) {
            return;
        }

        $context = \context_course::instance($COURSE->id);
        $ispicker = optional_param('unittours_pick', 0, PARAM_BOOL);
        if ($ispicker && has_capability('local/unittours:manage', $context) && confirm_sesskey()) {
            self::init_target_picker($COURSE->id);
            return;
        }

        if (!has_capability('local/unittours:view', $context)) {
            return;
        }

        self::init_player($context);
    }

    /**
     * Initialise the target picker AMD module for tour editors.
     *
     * @param int $courseid Course id.
     */
    private static function init_target_picker(int $courseid): void {
        global $PAGE;

        $tourid = required_param('tourid', PARAM_INT);
        $stepid = optional_param('stepid', 0, PARAM_INT);
        $returnparams = [
            'id' => $courseid,
            'tourid' => $tourid,
        ];
        if ($stepid) {
            $returnparams['stepid'] = $stepid;
        }

        $PAGE->requires->js_call_amd('local_unittours/target_picker', 'init', [[
            'returnurl' => (new \moodle_url('/local/unittours/edit_step.php', $returnparams))->out(false),
            'strings' => [
                'picktarget' => get_string('picktarget', 'local_unittours'),
                'picktargetinstructions' => get_string('picktargetinstructions', 'local_unittours'),
                'cancel' => get_string('cancel'),
            ],
        ]]);
    }

    /**
     * Initialise the tour player AMD module with the tours visible to the current user.
     *
     * @param \context_course $context Course context of the page being rendered.
     */
    private static function init_player(\context_course $context): void {
        global $COURSE, $PAGE, $USER;

        $courseid = (int) $COURSE->id;
        $userid = (int) $USER->id;
        $canmanage = has_capability('local/unittours:manage', $context);
        $audience = $canmanage ? 'staff' : 'student';

        $enabledtours = tour_repository::get_enabled_tours_for_course($courseid, $audience, $userid);
        if (!$enabledtours) {
            return;
        }

        // Compute which tours should auto-run from the already-fetched enabled set:
        // a tour auto-runs while incomplete, or always when so configured.
        $completedids = tour_repository::get_completed_tourids($courseid, $userid);
        $autorunids = [];
        foreach ($enabledtours as $tour) {
            if ($tour->showmode === 'always' || !in_array((int) $tour->id, $completedids, true)) {
                $autorunids[] = (int) $tour->id;
            }
        }

        $payload = [
            'courseid' => $courseid,
            'userid' => $userid,
            // Accepted risk: the sesskey is readable by any JavaScript running on the
            // page. This matches core practice (M.cfg.sesskey is exposed the same way);
            // it must not additionally be placed in data-* attributes or localStorage.
            'sesskey' => sesskey(),
            'starturl' => (new \moodle_url('/local/unittours/start.php'))->out(false),
            'completeurl' => (new \moodle_url('/local/unittours/complete.php'))->out(false),
            'reseturl' => (new \moodle_url('/local/unittours/reset_completion.php'))->out(false),
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

        $stepsbytour = tour_repository::get_steps_for_tours(array_map('intval', array_keys($enabledtours)));

        foreach ($enabledtours as $tour) {
            $steps = [];
            foreach ($stepsbytour[(int) $tour->id] ?? [] as $step) {
                $steps[] = [
                    'id' => (int) $step->id,
                    'title' => html_entity_decode(
                        format_string($step->title, true, ['context' => $context]),
                        ENT_QUOTES | ENT_HTML5
                    ),
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
}
