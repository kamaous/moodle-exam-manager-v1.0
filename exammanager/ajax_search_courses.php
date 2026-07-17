<?php
define('AJAX_SCRIPT', true);
require('../../config.php');
require_login();
require_sesskey();
$context = context_system::instance();
require_capability('local/exammanager:manage', $context);
$query = trim(required_param('query', PARAM_TEXT));
if (core_text::strlen($query) > 100) {
    throw new moodle_exception('invalidparameter', 'error');
}
$limit = optional_param('limit', 20, PARAM_INT);
$offset = optional_param('offset', 0, PARAM_INT);
$limit = max(1, min(50, $limit));
$offset = max(0, $offset);
$params = ['q1' => '%' . $query . '%', 'q2' => '%' . $query . '%'];
$sql = "SELECT id, shortname, fullname FROM {course}
        WHERE id > 1 AND (" . $DB->sql_like('shortname', ':q1', false) . " OR " . $DB->sql_like('fullname', ':q2', false) . ")
        ORDER BY shortname ASC";
$courses = $DB->get_records_sql($sql, $params, $offset, $limit + 1);
$results = []; $count = 0;
foreach ($courses as $course) {
    if ($count >= $limit) break;
    $shortname = format_string($course->shortname);
    $fullname = format_string($course->fullname);
    $results[] = ['id' => (int)$course->id, 'shortname' => $shortname, 'fullname' => $fullname, 'label' => $shortname . ' — ' . $fullname];
    $count++;
}
$hasmore = count($courses) > $limit;
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['results' => $results, 'hasmore' => $hasmore]);
exit;
