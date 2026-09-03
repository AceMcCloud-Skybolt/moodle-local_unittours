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
 * Renderer for Unit tours management pages.
 *
 * @package    local_unittours
 * @copyright  2026 Murdoch University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_unittours\output;

use context_course;
use html_writer;
use local_unittours\local\target_resolver;
use moodle_url;
use plugin_renderer_base;
use single_button;
use stdClass;

/**
 * Theme-overridable renderer for Unit tours.
 */
class renderer extends plugin_renderer_base {
    /**
     * Render the course tour list.
     *
     * @param stdClass[] $tours Tour records.
     * @param int[] $stepcounts Step counts keyed by tour id.
     * @param stdClass $course Course record.
     * @param context_course $context Course context.
     * @return string Rendered HTML.
     */
    public function manage_page(array $tours, array $stepcounts, stdClass $course, context_course $context): string {
        $manageurl = new moodle_url('/local/unittours/manage.php', ['id' => $course->id]);
        $rows = [];
        foreach ($tours as $tour) {
            $rows[] = [
                'name' => format_string($tour->name, true, ['context' => $context]),
                'viewurl' => (new moodle_url('/local/unittours/view.php', [
                    'id' => $course->id,
                    'tourid' => $tour->id,
                ]))->out(false),
                'status' => $tour->enabled
                    ? get_string('enabled', 'local_unittours')
                    : get_string('disabled', 'local_unittours'),
                'stepcount' => $stepcounts[(int) $tour->id] ?? 0,
                'editurl' => (new moodle_url('/local/unittours/edit_tour.php', [
                    'id' => $course->id,
                    'tourid' => $tour->id,
                ]))->out(false),
                'deleteurl' => (new moodle_url($manageurl, [
                    'action' => 'deletetour',
                    'tourid' => $tour->id,
                ]))->out(false),
            ];
        }

        return $this->render_from_template('local_unittours/manage_page', [
            'createbutton' => $this->single_button(
                new moodle_url($manageurl, ['action' => 'createtour']),
                get_string('createtour', 'local_unittours'),
                'post'
            ),
            'hasrows' => !empty($rows),
            'rows' => $rows,
            'notours' => get_string('notours', 'local_unittours'),
            'tourname' => get_string('tourname', 'local_unittours'),
            'statuslabel' => get_string('status', 'local_unittours'),
            'stepslabel' => get_string('steps', 'local_unittours'),
            'actionslabel' => get_string('actions'),
            'editlabel' => get_string('edit'),
            'deletelabel' => get_string('delete'),
        ]);
    }

    /**
     * Render one tour and its steps.
     *
     * @param stdClass $tour Tour record.
     * @param stdClass[] $steps Step records.
     * @param stdClass $course Course record.
     * @param context_course $context Course context.
     * @return string Rendered HTML.
     */
    public function tour_page(stdClass $tour, array $steps, stdClass $course, context_course $context): string {
        $viewurl = new moodle_url('/local/unittours/view.php', ['id' => $course->id, 'tourid' => $tour->id]);
        $rows = [];
        foreach ($steps as $step) {
            $targetinfo = target_resolver::describe($step, $course);
            $rows[] = [
                'title' => format_string($step->title, true, ['context' => $context]),
                'targettype' => get_string('target_' . $step->targettype, 'local_unittours'),
                'targetlabel' => $targetinfo->label,
                'targethealth' => $this->target_health($targetinfo),
                'audiostatus' => $this->audio_status($step),
                'placement' => get_string('placement_' . $step->placement, 'local_unittours'),
                'moveup' => $this->step_action_button($viewurl, 'movestepup', (int) $step->id, 'moveup'),
                'movedown' => $this->step_action_button($viewurl, 'movestepdown', (int) $step->id, 'movedown'),
                'editurl' => (new moodle_url('/local/unittours/edit_step.php', [
                    'id' => $course->id,
                    'tourid' => $tour->id,
                    'stepid' => $step->id,
                ]))->out(false),
                'deleteurl' => (new moodle_url($viewurl, [
                    'action' => 'deletestep',
                    'stepid' => $step->id,
                ]))->out(false),
            ];
        }

        return $this->render_from_template('local_unittours/tour_page', [
            'editurl' => (new moodle_url('/local/unittours/edit_tour.php', [
                'id' => $course->id,
                'tourid' => $tour->id,
            ]))->out(false),
            'addstepurl' => (new moodle_url('/local/unittours/edit_step.php', [
                'id' => $course->id,
                'tourid' => $tour->id,
            ]))->out(false),
            'resetbutton' => $this->single_button(
                new moodle_url($viewurl, ['action' => 'resetmycompletion']),
                get_string('resetmycompletion', 'local_unittours'),
                'post',
                ['class' => 'btn btn-secondary']
            ),
            'description' => empty($tour->description) ? '' : format_text(
                $tour->description,
                $tour->descriptionformat,
                ['context' => $context]
            ),
            'hasrows' => !empty($rows),
            'rows' => $rows,
            'nosteps' => get_string('nosteps', 'local_unittours'),
            'edittour' => get_string('edittour', 'local_unittours'),
            'addstep' => get_string('addstep', 'local_unittours'),
            'steptitle' => get_string('steptitle', 'local_unittours'),
            'targettypeheader' => get_string('targettype', 'local_unittours'),
            'targetlabelheader' => get_string('targetlabel', 'local_unittours'),
            'targethealthheader' => get_string('targethealth', 'local_unittours'),
            'audiostatusheader' => get_string('audiostatus', 'local_unittours'),
            'placementheader' => get_string('placement', 'local_unittours'),
            'actionslabel' => get_string('actions'),
            'editlabel' => get_string('edit'),
            'deletelabel' => get_string('delete'),
        ]);
    }

    /**
     * Render a target health badge and optional detail.
     *
     * @param stdClass $targetinfo Target resolution result.
     * @return string HTML.
     */
    private function target_health(stdClass $targetinfo): string {
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
        return $health;
    }

    /**
     * Render the audio status for a step.
     *
     * @param stdClass $step Step record.
     * @return string HTML.
     */
    private function audio_status(stdClass $step): string {
        if (empty($step->audioenabled)) {
            return html_writer::span(get_string('audiooff', 'local_unittours'), 'badge badge-secondary');
        }
        if (trim((string) ($step->audiotext ?? '')) === '') {
            return html_writer::span(get_string('audioneedstext', 'local_unittours'), 'badge badge-warning');
        }
        $label = get_string('audioonbrowserdependent', 'local_unittours');
        if (!empty($step->audiolang)) {
            $label .= ' (' . s($step->audiolang) . ')';
        }
        return html_writer::span($label, 'badge badge-info');
    }

    /**
     * Render a compact POST button for a step mutation.
     *
     * @param moodle_url $url Form action URL.
     * @param string $action Action name.
     * @param int $stepid Step id.
     * @param string $stringid Language string id.
     * @return string Form HTML.
     */
    private function step_action_button(moodle_url $url, string $action, int $stepid, string $stringid): string {
        $buttonurl = new moodle_url($url, ['action' => $action, 'stepid' => $stepid]);
        return $this->render(new single_button(
            $buttonurl,
            get_string($stringid, 'local_unittours'),
            'post',
            single_button::BUTTON_SECONDARY,
            ['class' => 'btn btn-link p-0 align-baseline']
        ));
    }
}
