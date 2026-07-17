<?php
require('../../config.php');
require_login();
require_sesskey();

$context = context_system::instance();
require_capability('local/exammanager:manage', $context);

$headers = [
    'course_shortname',
    'quiz_name',
    'open_time',
    'close_time',
    'time_limit'
];

$example = [
    [
        'INFO101',
        'Examen Final',
        '2026-06-15 09:00',
        '2026-06-15 11:00',
        '120'
    ]
];

$base = make_temp_directory('local_exammanager/template');
foreach (glob($base . '/*') as $tmpfile) {
    if (is_file($tmpfile) && filemtime($tmpfile) < (time() - 3600)) {
        @unlink($tmpfile);
    }
}
$file = $base . '/modele_examens.xlsx';

\local_exammanager\xlsx_writer::write($headers, $example, $file);

header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store, no-cache, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="modele_examens.xlsx"');
header('Content-Length: ' . filesize($file));

readfile($file);
@unlink($file);
exit;
