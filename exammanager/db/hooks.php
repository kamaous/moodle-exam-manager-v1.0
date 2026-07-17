<?php
defined('MOODLE_INTERNAL') || die();

$callbacks = [
    [
        'hook' => \core\hook\navigation\primary_extend::class,
        'callback' => \local_exammanager\hook_callbacks::class . '::primary_extend',
        'priority' => 0,
    ],
];
