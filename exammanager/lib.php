<?php
defined('MOODLE_INTERNAL') || die();

function local_exammanager_extend_navigation(global_navigation $navigation) {
    if (!isloggedin()) {
        return;
    }

    $context = context_system::instance();
    if (!has_capability('local/exammanager:manage', $context)) {
        return;
    }

    $node = navigation_node::create(
        get_string('pluginname', 'local_exammanager'),
        new moodle_url('/local/exammanager/dashboard.php'),
        navigation_node::TYPE_CUSTOM,
        null,
        'local_exammanager',
        new pix_icon('i/report', '')
    );

    $navigation->add_node($node);
}
