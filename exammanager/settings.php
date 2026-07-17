<?php
defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $ADMIN->add('tools', new admin_externalpage(
        'local_exammanager_dashboard',
        get_string('pluginname', 'local_exammanager'),
        new moodle_url('/local/exammanager/dashboard.php'),
        'local/exammanager:manage'
    ));
}
