<?php
// This file is part of Moodle - http://moodle.org/

namespace local_unittours\local;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/group/lib.php');

final class tour_repository {

    public static function get_tours_for_course(int $courseid): array {
        global $DB;

        return $DB->get_records(
            'local_unittours_tours',
            ['courseid' => $courseid],
            'sortorder ASC, id ASC'
        );
    }

    public static function count_steps(int $tourid): int {
        global $DB;

        return $DB->count_records('local_unittours_steps', ['tourid' => $tourid]);
    }

    public static function get_tour(int $tourid, int $courseid): \stdClass {
        global $DB;

        return $DB->get_record(
            'local_unittours_tours',
            ['id' => $tourid, 'courseid' => $courseid],
            '*',
            MUST_EXIST
        );
    }

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

    public static function get_steps_for_tour(int $tourid): array {
        global $DB;

        return $DB->get_records(
            'local_unittours_steps',
            ['tourid' => $tourid],
            'sortorder ASC, id ASC'
        );
    }

    public static function get_groupids_for_tour(int $tourid): array {
        global $DB;

        return array_map(
            'intval',
            $DB->get_fieldset_select('local_unittours_tour_groups', 'groupid', 'tourid = :tourid', ['tourid' => $tourid])
        );
    }

    public static function get_playable_tours_for_course(int $courseid, string $audience, int $userid): array {
        global $DB;

        [$audiencesql, $params] = $DB->get_in_or_equal(['all', $audience, 'group'], SQL_PARAMS_NAMED, 'audience');
        $params['courseid'] = $courseid;
        $params['userid'] = $userid;

        $sql = "SELECT t.*
                  FROM {local_unittours_tours} t
             LEFT JOIN {local_unittours_completion} c ON c.tourid = t.id AND c.userid = :userid
                 WHERE t.courseid = :courseid
                   AND t.enabled = 1
                   AND t.audience {$audiencesql}
                   AND (t.showmode = 'always' OR c.id IS NULL)
              ORDER BY t.sortorder ASC, t.id ASC";

        return self::filter_group_audience_tours($DB->get_records_sql($sql, $params), $courseid, $userid, $audience);
    }

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

    public static function can_user_access_tour(int $tourid, int $courseid, string $audience, int $userid): bool {
        foreach (self::get_enabled_tours_for_course($courseid, $audience, $userid) as $tour) {
            if ((int) $tour->id === $tourid) {
                return true;
            }
        }

        return false;
    }

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

    public static function delete_tour(int $tourid, int $courseid): void {
        global $DB;

        $tour = self::get_tour($tourid, $courseid);
        $DB->delete_records('local_unittours_completion', ['tourid' => $tour->id]);
        $DB->delete_records('local_unittours_tour_groups', ['tourid' => $tour->id]);
        $DB->delete_records('local_unittours_steps', ['tourid' => $tour->id]);
        $DB->delete_records('local_unittours_tours', ['id' => $tour->id]);
    }

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

    public static function delete_step(int $stepid, int $courseid): int {
        global $DB;

        $step = self::get_step($stepid, $courseid);
        $DB->delete_records('local_unittours_steps', ['id' => $step->id]);

        return (int) $step->tourid;
    }

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

    public static function mark_completion(int $tourid, int $courseid, int $userid, string $status): void {
        global $DB;

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
            return;
        }

        $DB->insert_record('local_unittours_completion', (object) [
            'tourid' => $tour->id,
            'userid' => $userid,
            'status' => $status,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
    }

    public static function clear_completion(int $tourid, int $courseid, int $userid): void {
        global $DB;

        $tour = self::get_tour($tourid, $courseid);
        $DB->delete_records('local_unittours_completion', [
            'tourid' => $tour->id,
            'userid' => $userid,
        ]);
    }

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

    public static function create_draft_tour(int $courseid): int {
        global $DB;

        $now = time();
        $sortorder = $DB->count_records('local_unittours_tours', ['courseid' => $courseid]);

        $tourid = $DB->insert_record('local_unittours_tours', (object) [
            'courseid' => $courseid,
            'name' => get_string('drafttourname', 'local_unittours'),
            'description' => get_string('drafttourdescription', 'local_unittours'),
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
            'title' => get_string('draftsteptitle', 'local_unittours'),
            'content' => get_string('draftstepcontent', 'local_unittours'),
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

    private static function filter_group_audience_tours(array $tours, int $courseid, int $userid, string $audience): array {
        if (!$tours) {
            return [];
        }

        $usergroupids = [];
        if ($userid) {
            $usergroupids = array_map('intval', groups_get_user_groups($courseid, $userid)[0] ?? []);
        }

        $filtered = [];
        foreach ($tours as $key => $tour) {
            if ($tour->audience !== 'group') {
                $filtered[$key] = $tour;
                continue;
            }

            $tourgroupids = self::get_groupids_for_tour((int) $tour->id);
            if ($audience === 'staff' || array_intersect($tourgroupids, $usergroupids)) {
                $filtered[$key] = $tour;
            }
        }

        return $filtered;
    }
}
