<?php
defined('MOODLE_INTERNAL') || die();

function xmldb_local_exammanager_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2026041801) {
        $table = new xmldb_table('local_exammanager_codes');

        $field = new xmldb_field('access_code_action', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'keep', 'seb_exit_code');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('seb_action', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'keep', 'access_code_action');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2026041801, 'local', 'exammanager');
    }

    return true;
}
