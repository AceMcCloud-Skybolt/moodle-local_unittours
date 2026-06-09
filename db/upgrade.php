<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

function xmldb_local_unittours_upgrade($oldversion): bool {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2026053100) {
        $table = new xmldb_table('local_unittours_steps');

        $field = new xmldb_field('audioenabled', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0', 'backdrop');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('audioautoplay', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0', 'audioenabled');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('audiotext', XMLDB_TYPE_TEXT, null, null, null, null, null, 'audioautoplay');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('audiolang', XMLDB_TYPE_CHAR, '16', null, null, null, null, 'audiotext');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2026053100, 'local', 'unittours');
    }

    if ($oldversion < 2026060900) {
        $table = new xmldb_table('local_unittours_tour_groups');

        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('tourid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('groupid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_key('tourid', XMLDB_KEY_FOREIGN, ['tourid'], 'local_unittours_tours', ['id']);

            $table->add_index('tourid-groupid', XMLDB_INDEX_UNIQUE, ['tourid', 'groupid']);
            $table->add_index('groupid', XMLDB_INDEX_NOTUNIQUE, ['groupid']);

            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2026060900, 'local', 'unittours');
    }

    return true;
}
