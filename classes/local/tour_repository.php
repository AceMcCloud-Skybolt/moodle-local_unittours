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

namespace local_unittours\local;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/group/lib.php');

/**
 * Data access layer for unit tours, steps, group audiences and completion records.
 *
 * @package    local_unittours
 * @copyright  2026 Murdoch University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class tour_repository {
    /**
     * Get all tours in a course, in display order.
     *
     * @param int $courseid Course id.
     * @return \stdClass[] Tour records keyed by id.
     */
    public static function get_tours_for_course(int $courseid): array {
        global $DB;

        return $DB->get_records(
            'local_unittours_tours',
            ['courseid' => $courseid],
            'sortorder ASC, id ASC'
        );
    }

    /**
     * Count the steps in a tour.
     *
     * @param int $tourid Tour id.
     * @return int Number of steps.
     */
    public static function count_steps(int $tourid): int {
        global $DB;

        return $DB->count_records('local_unittours_steps', ['tourid' => $tourid]);
    }

    /**
     * Get step counts for every tour in a course using a single query.
     *
     * @param int $courseid Course id.
     * @return int[] Map of tour id => step count (tours without steps are absent).
     */
    public static function get_step_counts_for_course(int $courseid): array {
        global $DB;

        $sql = "SELECT s.tourid, COUNT(s.id) AS stepcount
                  FROM {local_unittours_steps} s
                  JOIN {local_unittours_tours} t ON t.id = s.tourid
                 WHERE t.courseid = :courseid
              GROUP BY s.tourid";

        $counts = [];
        foreach ($DB->get_records_sql($sql, ['courseid' => $courseid]) as $record) {
            $counts[(int) $record->tourid] = (int) $record->stepcount;
        }

        return $counts;
    }

    /**
     * Get a tour, asserting it belongs to the given course.
     *
     * @param int $tourid Tour id.
     * @param int $courseid Course id the tour must belong to.
     * @return \stdClass Tour record.
     */
    public static function get_tour(int $tourid, int $courseid): \stdClass {
        global $DB;

        return $DB->get_record(
            'local_unittours_tours',
            ['id' => $tourid, 'courseid' => $courseid],
            '*',
            MUST_EXIST
        );
    }

    /**
     * Get a step, asserting its tour belongs to the given course.
     *
     * @param int $stepid Step id.
     * @param int $courseid Course id the parent tour must belong to.
     * @return \stdClass Step record.
     */
    public static function get_step(int $stepid, int $courseid): \stdClass {
        global $DB;

        $sql = "SELECT s.*
                  FROM {local_unittours_steps} s
                  JOIN {local_unittours_tours} t ON t.id = s.tourid
                 WHERE s.id = :stepid
                   AND t.courseid = :courseid";

        return $DB->get_record_sql($sql, [
            'stepid' => $stepid,
            'courseid' => $courseid,
        ], MUST_EXIST);
    }

    /**
     * Get the steps of a tour, in display order.
     *
     * @param int $tourid Tour id.
     * @return \stdClass[] Step records keyed by id.
     */
    public static function get_steps_for_tour(int $tourid): array {
        global $DB;

        return $DB->get_records(
            'local_unittours_steps',
            ['tourid' => $tourid],
            'sortorder ASC, id ASC'
        );
    }

    /**
     * Get the steps of multiple tours in a single query.
     *
     * @param int[] $tourids Tour ids.
     * @return array Map of tour id => step records in display order.
     */
    public static function get_steps_for_tours(array $tourids): array {
        global $DB;

        if (empty($tourids)) {
            return [];
        }

        [$insql, $params] = $DB->get_in_or_equal($tourids, SQL_PARAMS_NAMED, 'tourid');
        $records = $DB->get_records_select(
            'local_unittours_steps',
            "tourid {$insql}",
            $params,
            'tourid ASC, sortorder ASC, id ASC'
        );

        $steps = array_fill_keys(array_map('intval', $tourids), []);
        foreach ($records as $record) {
            $steps[(int) $record->tourid][] = $record;
        }

        return $steps;
    }

    /**
     * Get the group ids a tour is restricted to.
     *
     * @param int $tourid Tour id.
     * @return int[] Group ids.
     */
    public static function get_groupids_for_tour(int $tourid): array {
        global $DB;

        return array_map(
            'intval',
            $DB->get_fieldset_select('local_unittours_tour_groups', 'groupid', 'tourid = :tourid', ['tourid' => $tourid])
        );
    }

    /**
     * Get the group ids for multiple tours in a single query.
     *
     * @param int[] $tourids Tour ids.
     * @return array Map of tour id => group ids (tours without groups map to an empty array).
     */
    public static function get_groupids_for_tours(array $tourids): array {
        global $DB;

        if (empty($tourids)) {
            return [];
        }

        [$insql, $params] = $DB->get_in_or_equal($tourids, SQL_PARAMS_NAMED, 'tourid');
        $records = $DB->get_records_select('local_unittours_tour_groups', "tourid {$insql}", $params);

        $groupids = array_fill_keys(array_map('intval', $tourids), []);
        foreach ($records as $record) {
            $groupids[(int) $record->tourid][] = (int) $record->groupid;
        }

        return $groupids;
    }

    /**
     * Get the ids of tours in a course the user has a completion record for.
     *
     * @param int $courseid Course id.
     * @param int $userid User id.
     * @return int[] Tour ids.
     */
    public static function get_completed_tourids(int $courseid, int $userid): array {
        global $DB;

        $sql = "SELECT c.tourid
                  FROM {local_unittours_completion} c
                  JOIN {local_unittours_tours} t ON t.id = c.tourid
                 WHERE t.courseid = :courseid
                   AND c.userid = :userid";

        return array_map('intval', $DB->get_fieldset_sql($sql, [
            'courseid' => $courseid,
            'userid' => $userid,
        ]));
    }

    /**
     * Get the enabled tours in a course visible to the given audience, in display order.
     *
     * Group-audience tours are filtered to those the user is a member of (staff see all).
     *
     * @param int $courseid Course id.
     * @param string $audience Audience key ('student' or 'staff').
     * @param int $userid User id for group membership checks, 0 to skip them.
     * @return \stdClass[] Tour records keyed by id.
     */
    public static function get_enabled_tours_for_course(int $courseid, string $audience, int $userid = 0): array {
        global $DB;

        [$audiencesql, $params] = $DB->get_in_or_equal(['all', $audience, 'group'], SQL_PARAMS_NAMED, 'audience');
        $params['courseid'] = $courseid;

        $sql = "SELECT *
                  FROM {local_unittours_tours}
                 WHERE courseid = :courseid
                   AND enabled = 1
                   AND audience {$audiencesql}
              ORDER BY sortorder ASC, id ASC";

        return self::filter_group_audience_tours($DB->get_records_sql($sql, $params), $courseid, $userid, $audience);
    }

    /**
     * Check whether an enabled tour is visible to the user for the given audience.
     *
     * @param int $tourid Tour id.
     * @param int $courseid Course id.
     * @param string $audience Audience key ('student' or 'staff').
     * @param int $userid User id for group membership checks.
     * @return bool True if the user may access the tour.
     */
    public static function can_user_access_tour(int $tourid, int $courseid, string $audience, int $userid): bool {
        foreach (self::get_enabled_tours_for_course($courseid, $audience, $userid) as $tour) {
            if ((int) $tour->id === $tourid) {
                return true;
            }
        }

        return false;
    }

    /**
     * Create or update a tour from edit form data.
     *
     * @param \stdClass $data Data returned by the edit_tour form.
     * @param int $courseid Course id the tour belongs to.
     * @return int Tour id.
     */
    public static function save_tour(\stdClass $data, int $courseid): int {
        global $DB;

        $now = time();
        $record = (object) [
            'courseid' => $courseid,
            'name' => $data->name,
            'description' => $data->description['text'],
            'descriptionformat' => $data->description['format'],
            'enabled' => empty($data->enabled) ? 0 : 1,
            'audience' => $data->audience,
            'showmode' => $data->showmode,
            'timemodified' => $now,
        ];

        if (!empty($data->tourid)) {
            $existing = self::get_tour((int) $data->tourid, $courseid);
            $record->id = $existing->id;
            $DB->update_record('local_unittours_tours', $record);
            self::save_tour_groups($existing->id, $data, $courseid);
            return (int) $existing->id;
        }

        $record->sortorder = $DB->count_records('local_unittours_tours', ['courseid' => $courseid]);
        $record->timecreated = $now;

        $tourid = (int) $DB->insert_record('local_unittours_tours', $record);
        self::save_tour_groups($tourid, $data, $courseid);

        return $tourid;
    }

    /**
     * Create or update a step from edit form data.
     *
     * @param \stdClass $data Data returned by the edit_step form.
     * @param int $courseid Course id the parent tour belongs to.
     * @return int Step id.
     */
    public static function save_step(\stdClass $data, int $courseid): int {
        global $DB;

        $existing = null;
        if (!empty($data->stepid)) {
            $existing = self::get_step((int) $data->stepid, $courseid);
            $data->tourid = $existing->tourid;
        }

        $tour = self::get_tour((int) $data->tourid, $courseid);
        $now = time();
        $record = (object) [
            'tourid' => $tour->id,
            'title' => $data->title,
            'content' => $data->content['text'],
            'contentformat' => $data->content['format'],
            'targettype' => $data->targettype,
            'targetref' => $data->targetref,
            'fallbackselector' => $data->fallbackselector,
            'placement' => $data->placement,
            'showiftargetmissing' => empty($data->showiftargetmissing) ? 0 : 1,
            'backdrop' => empty($data->backdrop) ? 0 : 1,
            'audioenabled' => empty($data->audioenabled) ? 0 : 1,
            'audioautoplay' => empty($data->audioautoplay) ? 0 : 1,
            'audiotext' => $data->audiotext,
            'audiolang' => $data->audiolang,
            'timemodified' => $now,
        ];

        if ($existing) {
            $record->id = $existing->id;
            $DB->update_record('local_unittours_steps', $record);
            return (int) $existing->id;
        }

        $record->sortorder = $DB->count_records('local_unittours_steps', ['tourid' => $tour->id]);
        $record->timecreated = $now;

        return (int) $DB->insert_record('local_unittours_steps', $record);
    }

    /**
     * Delete a tour with its steps, group audiences and completion records.
     *
     * @param int $tourid Tour id.
     * @param int $courseid Course id the tour must belong to.
     */
    public static function delete_tour(int $tourid, int $courseid): void {
        global $DB;

        $tour = self::get_tour($tourid, $courseid);
        $DB->delete_records('local_unittours_completion', ['tourid' => $tour->id]);
        $DB->delete_records('local_unittours_tour_groups', ['tourid' => $tour->id]);
        $DB->delete_records('local_unittours_steps', ['tourid' => $tour->id]);
        $DB->delete_records('local_unittours_tours', ['id' => $tour->id]);
    }

    /**
     * Delete all plugin data for a course (used when a course is deleted).
     *
     * @param int $courseid Course id.
     */
    public static function delete_course_data(int $courseid): void {
        global $DB;

        $tourids = $DB->get_fieldset_select(
            'local_unittours_tours',
            'id',
            'courseid = :courseid',
            ['courseid' => $courseid]
        );

        if (empty($tourids)) {
            return;
        }

        [$insql, $params] = $DB->get_in_or_equal($tourids, SQL_PARAMS_NAMED, 'tourid');
        $DB->delete_records_select('local_unittours_completion', "tourid {$insql}", $params);
        $DB->delete_records_select('local_unittours_tour_groups', "tourid {$insql}", $params);
        $DB->delete_records_select('local_unittours_steps', "tourid {$insql}", $params);
        $DB->delete_records_select('local_unittours_tours', "id {$insql}", $params);
    }

    /**
     * Delete a step.
     *
     * @param int $stepid Step id.
     * @param int $courseid Course id the parent tour must belong to.
     * @return int Id of the tour the step belonged to.
     */
    public static function delete_step(int $stepid, int $courseid): int {
        global $DB;

        $step = self::get_step($stepid, $courseid);
        $DB->delete_records('local_unittours_steps', ['id' => $step->id]);

        return (int) $step->tourid;
    }

    /**
     * Swap a step's sort order with its neighbour in the given direction.
     *
     * @param int $stepid Step id.
     * @param int $courseid Course id the parent tour must belong to.
     * @param string $direction 'up' or 'down'.
     * @return int Id of the tour the step belongs to.
     */
    public static function move_step(int $stepid, int $courseid, string $direction): int {
        global $DB;

        $step = self::get_step($stepid, $courseid);
        $order = ($direction === 'up') ? 'sortorder DESC, id DESC' : 'sortorder ASC, id ASC';
        $operator = ($direction === 'up') ? '<' : '>';

        $neighbour = $DB->get_records_select(
            'local_unittours_steps',
            "tourid = :tourid AND sortorder {$operator} :sortorder",
            ['tourid' => $step->tourid, 'sortorder' => $step->sortorder],
            $order,
            '*',
            0,
            1
        );
        $neighbour = $neighbour ? reset($neighbour) : null;

        if (!$neighbour) {
            return (int) $step->tourid;
        }

        $stepsort = (int) $step->sortorder;
        $step->sortorder = (int) $neighbour->sortorder;
        $neighbour->sortorder = $stepsort;
        $step->timemodified = time();
        $neighbour->timemodified = time();

        $DB->update_record('local_unittours_steps', $step);
        $DB->update_record('local_unittours_steps', $neighbour);

        return (int) $step->tourid;
    }

    /**
     * Record that a user completed or skipped a tour, then trigger the matching event.
     *
     * @param int $tourid Tour id.
     * @param int $courseid Course id the tour must belong to.
     * @param int $userid User id.
     * @param string $status 'complete' or 'skipped'.
     */
    public static function mark_completion(int $tourid, int $courseid, int $userid, string $status): void {
        global $DB;

        if (!in_array($status, ['complete', 'skipped'], true)) {
            throw new \invalid_parameter_exception('Invalid completion status.');
        }

        $tour = self::get_tour($tourid, $courseid);
        $now = time();
        $existing = $DB->get_record('local_unittours_completion', [
            'tourid' => $tour->id,
            'userid' => $userid,
        ]);

        if ($existing) {
            $existing->status = $status;
            $existing->timemodified = $now;
            $DB->update_record('local_unittours_completion', $existing);
            self::trigger_completion_event($existing, $tour, $courseid, $userid, $status);
            return;
        }

        $record = (object) [
            'tourid' => $tour->id,
            'userid' => $userid,
            'status' => $status,
            'timecreated' => $now,
            'timemodified' => $now,
        ];

        try {
            $record->id = $DB->insert_record('local_unittours_completion', $record);
        } catch (\dml_write_exception $exception) {
            $existing = $DB->get_record('local_unittours_completion', [
                'tourid' => $tour->id,
                'userid' => $userid,
            ], '*', MUST_EXIST);
            $existing->status = $status;
            $existing->timemodified = $now;
            $DB->update_record('local_unittours_completion', $existing);
            $record = $existing;
        }

        self::trigger_completion_event($record, $tour, $courseid, $userid, $status);
    }

    /**
     * Trigger the tour started event for a user.
     *
     * @param int $tourid Tour id.
     * @param int $courseid Course id the tour must belong to.
     * @param int $userid User id.
     */
    public static function mark_started(int $tourid, int $courseid, int $userid): void {
        $tour = self::get_tour($tourid, $courseid);
        $context = \context_course::instance($courseid);

        \local_unittours\event\tour_started::create([
            'context' => $context,
            'courseid' => $courseid,
            'objectid' => $tour->id,
            'userid' => $userid,
        ])->trigger();
    }

    /**
     * Remove a user's completion record for one tour.
     *
     * @param int $tourid Tour id.
     * @param int $courseid Course id the tour must belong to.
     * @param int $userid User id.
     */
    public static function clear_completion(int $tourid, int $courseid, int $userid): void {
        global $DB;

        $tour = self::get_tour($tourid, $courseid);
        $DB->delete_records('local_unittours_completion', [
            'tourid' => $tour->id,
            'userid' => $userid,
        ]);
    }

    /**
     * Remove a user's completion records for every tour in a course.
     *
     * @param int $courseid Course id.
     * @param int $userid User id.
     */
    public static function clear_completion_for_course(int $courseid, int $userid): void {
        global $DB;

        $tourids = $DB->get_fieldset_select(
            'local_unittours_tours',
            'id',
            'courseid = :courseid',
            ['courseid' => $courseid]
        );

        if (empty($tourids)) {
            return;
        }

        [$insql, $params] = $DB->get_in_or_equal($tourids, SQL_PARAMS_NAMED);
        $DB->delete_records_select(
            'local_unittours_completion',
            "tourid {$insql} AND userid = :userid",
            $params + ['userid' => $userid]
        );
    }

    /**
     * Create a disabled draft tour with one placeholder step and return its id.
     *
     * The placeholder name and step content are deliberately hardcoded English literals
     * rather than get_string() calls: the values are stored in the database at creation
     * time, so language-pack strings would freeze in whatever language the creator was
     * using and produce mismatched content on multi-language sites or after restores.
     * Editors are expected to replace them immediately.
     *
     * @param int $courseid Course id.
     * @return int New tour id.
     */
    public static function create_draft_tour(int $courseid): int {
        global $DB;

        $now = time();
        $sortorder = $DB->count_records('local_unittours_tours', ['courseid' => $courseid]);

        $tourid = $DB->insert_record('local_unittours_tours', (object) [
            'courseid' => $courseid,
            'name' => 'New student orientation',
            'description' => 'A draft tour for this unit.',
            'descriptionformat' => FORMAT_HTML,
            'enabled' => 0,
            'audience' => 'student',
            'showmode' => 'untilcomplete',
            'sortorder' => $sortorder,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);

        $DB->insert_record('local_unittours_steps', (object) [
            'tourid' => $tourid,
            'title' => 'Welcome to the unit',
            'content' => 'Use this first step to introduce students to the most important parts of the unit site.',
            'contentformat' => FORMAT_HTML,
            'targettype' => target::UNATTACHED,
            'targetref' => null,
            'fallbackselector' => null,
            'placement' => 'bottom',
            'showiftargetmissing' => 1,
            'backdrop' => 0,
            'audioenabled' => 0,
            'audioautoplay' => 0,
            'audiotext' => null,
            'audiolang' => null,
            'sortorder' => 0,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);

        return $tourid;
    }

    /**
     * Replace a tour's group audience rows from edit form data.
     *
     * @param int $tourid Tour id.
     * @param \stdClass $data Data returned by the edit_tour form.
     * @param int $courseid Course id used to validate the submitted groups.
     */
    private static function save_tour_groups(int $tourid, \stdClass $data, int $courseid): void {
        global $DB;

        if (($data->audience ?? '') !== 'group' || empty($data->groupids)) {
            $DB->delete_records('local_unittours_tour_groups', ['tourid' => $tourid]);
            return;
        }

        $groupids = [];
        foreach (array_unique(array_map('intval', (array) $data->groupids)) as $groupid) {
            if ($groupid <= 0) {
                continue;
            }
            if (!$DB->record_exists('groups', ['id' => $groupid, 'courseid' => $courseid])) {
                throw new \invalid_parameter_exception('Invalid group for this course.');
            }
            $groupids[] = $groupid;
        }

        $DB->delete_records('local_unittours_tour_groups', ['tourid' => $tourid]);
        foreach ($groupids as $groupid) {
            $DB->insert_record('local_unittours_tour_groups', (object) [
                'tourid' => $tourid,
                'groupid' => $groupid,
            ]);
        }
    }

    /**
     * Remove group-audience tours the user cannot see (staff see all of them).
     *
     * @param \stdClass[] $tours Tour records keyed by id.
     * @param int $courseid Course id.
     * @param int $userid User id for group membership checks, 0 to skip them.
     * @param string $audience Audience key ('student' or 'staff').
     * @return \stdClass[] Filtered tour records keyed by id.
     */
    private static function filter_group_audience_tours(array $tours, int $courseid, int $userid, string $audience): array {
        if (!$tours) {
            return [];
        }

        $grouptourids = [];
        foreach ($tours as $tour) {
            if ($tour->audience === 'group') {
                $grouptourids[] = (int) $tour->id;
            }
        }

        if (!$grouptourids) {
            return $tours;
        }

        $usergroupids = [];
        if ($userid) {
            $usergroupids = array_map('intval', groups_get_user_groups($courseid, $userid)[0] ?? []);
        }

        $tourgroupids = self::get_groupids_for_tours($grouptourids);

        $filtered = [];
        foreach ($tours as $key => $tour) {
            if ($tour->audience !== 'group') {
                $filtered[$key] = $tour;
                continue;
            }

            if ($audience === 'staff' || array_intersect($tourgroupids[(int) $tour->id] ?? [], $usergroupids)) {
                $filtered[$key] = $tour;
            }
        }

        return $filtered;
    }

    /**
     * Trigger the tour completed or skipped event for a completion record.
     *
     * @param \stdClass $completion Completion record.
     * @param \stdClass $tour Tour record.
     * @param int $courseid Course id.
     * @param int $userid User id.
     * @param string $status 'complete' or 'skipped'.
     */
    private static function trigger_completion_event(
        \stdClass $completion,
        \stdClass $tour,
        int $courseid,
        int $userid,
        string $status
    ): void {
        $eventclass = $status === 'skipped'
            ? \local_unittours\event\tour_skipped::class
            : \local_unittours\event\tour_completed::class;

        $eventclass::create([
            'context' => \context_course::instance($courseid),
            'courseid' => $courseid,
            'objectid' => $completion->id,
            'userid' => $userid,
            'other' => [
                'tourid' => (int) $tour->id,
            ],
        ])->trigger();
    }
}
