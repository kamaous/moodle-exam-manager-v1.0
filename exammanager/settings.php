<?php
defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $ADMIN->add('tools', new admin_externalpage(
        'local_exammanager',
        get_string('pluginname', 'local_exammanager'),
        new moodle_url('/local/exammanager/index.php'),
        'local/exammanager:manage'
    ));
}
