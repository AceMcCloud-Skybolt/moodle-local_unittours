<?php
// This file is part of Moodle - http://moodle.org/

namespace local_unittours\privacy;

use context;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\core_userlist_provider;
use core_privacy\local\metadata\collection;
use core_privacy\local\metadata\provider as metadataprovider;
use core_privacy\local\request\plugin\provider as requestprovider;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

defined('MOODLE_INTERNAL') || die();

class provider implements metadataprovider, requestprovider, core_userlist_provider {

    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table(
            'local_unittours_completion',
            [
                'tourid' => 'privacy:metadata:completion:tourid',
                'userid' => 'privacy:metadata:completion:userid',
                'status' => 'privacy:metadata:completion:status',
                'timemodified' => 'privacy:metadata:completion:timemodified',
            ],
            'privacy:metadata:completion'
        );

        return $collection;
    }

    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();
        $sql = "SELECT ctx.id
                  FROM {context} ctx
                  JOIN {local_unittours_tours} t ON t.courseid = ctx.instanceid
                  JOIN {local_unittours_completion} c ON c.tourid = t.id
                 WHERE ctx.contextlevel = :contextlevel
                   AND c.userid = :userid";

        $contextlist->add_from_sql($sql, [
            'contextlevel' => CONTEXT_COURSE,
            'userid' => $userid,
        ]);

        return $contextlist;
    }

    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel !== CONTEXT_COURSE) {
                continue;
            }

            $sql = "SELECT c.id, t.name, c.status, c.timemodified
                      FROM {local_unittours_completion} c
                      JOIN {local_unittours_tours} t ON t.id = c.tourid
                     WHERE t.courseid = :courseid
                       AND c.userid = :userid
                  ORDER BY t.sortorder ASC, t.id ASC";
            $records = $DB->get_records_sql($sql, [
                'courseid' => $context->instanceid,
                'userid' => $userid,
            ]);

            if ($records) {
                writer::with_context($context)->export_data(
                    [get_string('pluginname', 'local_unittours')],
                    (object) ['completion' => array_values($records)]
                );
            }
        }
    }

    public static function delete_data_for_all_users_in_context(context $context): void {
        global $DB;

        if ($context->contextlevel !== CONTEXT_COURSE) {
            return;
        }

        $tourids = $DB->get_fieldset_select(
            'local_unittours_tours',
            'id',
            'courseid = :courseid',
            ['courseid' => $context->instanceid]
        );

        if ($tourids) {
            [$insql, $params] = $DB->get_in_or_equal($tourids, SQL_PARAMS_NAMED);
            $DB->delete_records_select('local_unittours_completion', "tourid {$insql}", $params);
        }
    }

    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel !== CONTEXT_COURSE) {
                continue;
            }

            $sql = "userid = :userid AND tourid IN (
                    SELECT id FROM {local_unittours_tours} WHERE courseid = :courseid
                )";
            $DB->delete_records_select('local_unittours_completion', $sql, [
                'userid' => $userid,
                'courseid' => $context->instanceid,
            ]);
        }
    }

    public static function get_users_in_context(userlist $userlist): void {
        $context = $userlist->get_context();
        if ($context->contextlevel !== CONTEXT_COURSE) {
            return;
        }

        $sql = "SELECT c.userid
                  FROM {local_unittours_completion} c
                  JOIN {local_unittours_tours} t ON t.id = c.tourid
                 WHERE t.courseid = :courseid";
        $userlist->add_from_sql('userid', $sql, ['courseid' => $context->instanceid]);
    }

    public static function delete_data_for_users(approved_userlist $userlist): void {
        global $DB;

        $context = $userlist->get_context();
        if ($context->contextlevel !== CONTEXT_COURSE) {
            return;
        }

        $userids = $userlist->get_userids();
        if (!$userids) {
            return;
        }

        [$usersql, $userparams] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED, 'userid');
        $sql = "userid {$usersql} AND tourid IN (
                SELECT id FROM {local_unittours_tours} WHERE courseid = :courseid
            )";
        $DB->delete_records_select('local_unittours_completion', $sql, $userparams + [
            'courseid' => $context->instanceid,
        ]);
    }
}
