<?php
require('../../config.php');
require_login();

$context = context_system::instance();
require_capability('local/exammanager:manage', $context);

$type = required_param('type', PARAM_ALPHA);
$userid = $USER->id;

$map = [
    'csv' => [
        'file' => \local_exammanager\util::tempfile($userid,'codes_examens.csv'),
        'name' => 'codes_examens.csv',
        'mime' => 'text/csv'
    ],
    'excel' => [
        'file' => \local_exammanager\util::tempfile($userid,'codes_examens.xls'),
        'name' => 'codes_examens.xls',
        'mime' => 'application/vnd.ms-excel'
    ],
    'pdf' => [
        'file' => \local_exammanager\util::tempfile($userid,'codes_surveillants.pdf'),
        'name' => 'codes_surveillants.pdf',
        'mime' => 'application/pdf'
    ],
    'log' => [
        'file' => \local_exammanager\util::tempfile($userid,'journal_execution.txt'),
        'name' => 'journal_execution.txt',
        'mime' => 'text/plain'
    ]
];

if (!isset($map[$type])) {
    throw new moodle_exception('Type invalide');
}

$file = $map[$type]['file'];

if (!file_exists($file)) {
    throw new moodle_exception('Fichier introuvable');
}

header('Content-Type: '.$map[$type]['mime']);
header('Content-Disposition: attachment; filename="'.$map[$type]['name'].'"');
header('Content-Length: '.filesize($file));

readfile($file);
exit;