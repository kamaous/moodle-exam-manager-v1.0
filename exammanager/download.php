<?php

require('../../config.php');
require_login();
require_sesskey();

$context = context_system::instance();
require_capability('local/exammanager:manage', $context);

$type = required_param('type', PARAM_ALPHA);

$base = make_temp_directory('local_exammanager/' . $USER->id);

foreach (glob($base . '/*') as $tmpfile) {
    if (is_file($tmpfile) && filemtime($tmpfile) < (time() - 3600)) {
        @unlink($tmpfile);
    }
}

$files = [
    'csv' => [
        'file' => $base . '/examens.csv',
        'name' => 'Examens_' . date('Y-m-d_H-i') . '.csv',
        'mime' => 'text/csv'
    ],
    'excel' => [
        'file' => $base . '/examens.xlsx',
        'name' => 'Examens_' . date('Y-m-d_H-i') . '.xlsx',
        'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    ],
    'log' => [
        'file' => $base . '/execution.txt',
        'name' => 'Execution_' . date('Y-m-d_H-i') . '.txt',
        'mime' => 'text/plain'
    ],
];

if (!isset($files[$type])) {
    throw new moodle_exception('Type de téléchargement invalide');
}

$info = $files[$type];

if (!file_exists($info['file'])) {
    throw new moodle_exception('Fichier introuvable');
}

$realbase = realpath($base);
$realfile = realpath($info['file']);
if ($realbase === false || $realfile === false || strpos($realfile, $realbase) !== 0) {
    throw new moodle_exception('invalidaccess');
}

header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store, no-cache, must-revalidate');
header('Pragma: no-cache');
header('Content-Type: ' . $info['mime']);
header('Content-Disposition: attachment; filename="' . $info['name'] . '"');
header('Content-Length: ' . filesize($info['file']));

readfile($info['file']);
exit;