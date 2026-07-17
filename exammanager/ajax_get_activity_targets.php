<?php
define('AJAX_SCRIPT', true);
require('../../config.php');
require_login();
require_sesskey();

$context = context_system::instance();
require_capability('local/exammanager:manage', $context);

$shortname = trim(required_param('shortname', PARAM_TEXT));
if (core_text::strlen($shortname) > 100) {
    throw new moodle_exception('invalidparameter', 'error');
}

$response = [
    'success' => false,
    'sections' => [],
    'activities' => [],
    'message' => 'Shortname du cours introuvable.',
];

$course = \local_exammanager\activity_planner::get_course_by_shortname($shortname);
if ($course) {
    $coursecontext = context_course::instance((int)$course->id);
    require_capability('moodle/course:view', $coursecontext);

    $sections = [];
    foreach (\local_exammanager\activity_planner::get_course_sections($course) as $section) {
        $sections[] = [
            'value' => (string)$section['sectionnum'],
            'label' => (string)$section['label'],
            'sectionnum' => (string)$section['sectionnum'],
        ];
    }

    $activities = [];
    foreach (\local_exammanager\activity_planner::get_course_activities($course) as $activity) {
        $activities[] = [
            'value' => (string)$activity['name'],
            'label' => (string)$activity['sectionlabel'] . ' - ' . (string)$activity['name'],
            'sectionnum' => (string)$activity['sectionnum'],
            'sectionlabel' => (string)$activity['sectionlabel'],
            'name' => (string)$activity['name'],
            'modname' => (string)$activity['modname'],
        ];
    }

    $response = [
        'success' => true,
        'sections' => $sections,
        'activities' => $activities,
        'message' => '',
    ];
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
exit;
