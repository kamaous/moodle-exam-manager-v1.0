<?php
define('AJAX_SCRIPT', true);
require('../../config.php');
require_login();
require_sesskey();
$context = context_system::instance();
require_capability('local/exammanager:manage', $context);
$courseid = required_param('courseid', PARAM_INT);

$course = get_course($courseid);
$coursecontext = context_course::instance($courseid);
require_capability('moodle/course:view', $coursecontext);

$quizzes = $DB->get_records_sql(
    "SELECT DISTINCT q.id, q.name
       FROM {quiz} q
       JOIN {modules} m ON m.name = 'quiz'
       JOIN {course_modules} cm ON cm.instance = q.id
            AND cm.module = m.id
            AND cm.course = q.course
            AND cm.deletioninprogress = 0
      WHERE q.course = ?
   ORDER BY q.name ASC, q.id DESC",
    [$courseid]
);
$result = [];
foreach ($quizzes as $quiz) {
    $result[] = ['id' => (int)$quiz->id, 'name' => format_string($quiz->name)];
}
header('Content-Type: application/json; charset=utf-8');
echo json_encode($result);
exit;
