<?php
require('../../config.php');
require_login();

$context = context_system::instance();
require_capability('local/exammanager:manage', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/exammanager/index.php'));
$PAGE->set_title(get_string('pluginname', 'local_exammanager'));
$PAGE->set_heading(get_string('pluginname', 'local_exammanager'));

$userid = $USER->id;
$csvexport = \local_exammanager\util::tempfile($userid, 'codes_examens.csv');
$excelexport = \local_exammanager\util::tempfile($userid, 'codes_examens.xls');
$logexport = \local_exammanager\util::tempfile($userid, 'journal_execution.txt');
$pdfexport = \local_exammanager\util::tempfile($userid, 'codes_surveillants.pdf');
$uploadtargetbase = \local_exammanager\util::tempfile($userid, 'planning_upload');

$action = optional_param('action', '', PARAM_ALPHA);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('title', 'local_exammanager'));
echo html_writer::tag('p', get_string('requiredcolumns', 'local_exammanager'));
echo html_writer::tag('p', get_string('optionalcolumns', 'local_exammanager'));
echo html_writer::tag('p', get_string('helptext', 'local_exammanager'));

$results = [];
$downloadlinks = [];
$sessionkey = 'local_exammanager_rows_' . $userid;

echo html_writer::start_tag('form', ['method' => 'post', 'enctype' => 'multipart/form-data']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'preview']);
echo html_writer::tag('label', get_string('uploadlabel', 'local_exammanager'));
echo html_writer::empty_tag('br');
echo html_writer::empty_tag('input', ['type' => 'file', 'name' => 'planningfile', 'accept' => '.csv,.xlsx,.xls', 'required' => 'required']);
echo html_writer::empty_tag('br');
echo html_writer::empty_tag('br');
echo html_writer::empty_tag('input', ['type' => 'submit', 'value' => get_string('previewplanning', 'local_exammanager')]);
echo html_writer::end_tag('form');

if ($action === 'preview' && confirm_sesskey()) {
    if (!isset($_FILES['planningfile']) || empty($_FILES['planningfile']['tmp_name'])) {
        echo $OUTPUT->notification(get_string('nofile', 'local_exammanager'), 'notifyproblem');
    } else {
        $originalname = $_FILES['planningfile']['name'];
        $ext = strtolower(pathinfo($originalname, PATHINFO_EXTENSION));
        $target = $uploadtargetbase . '.' . $ext;
        move_uploaded_file($_FILES['planningfile']['tmp_name'], $target);

        try {
            $rows = \local_exammanager\reader::read_rows($target);
            $usedcodes = [];
            foreach ($rows as &$row) {
                list($valid, $msg) = \local_exammanager\util::validate_row($row);
                if (!$valid) {
                    $row['status'] = 'ERROR';
                    $row['message'] = $msg;
                    $row['access_code'] = '';
                    $row['seb_exit_code'] = '';
                    continue;
                }
                $generateaccess = isset($row['generate_access_code']) ? (int)$row['generate_access_code'] : 1;
                $generateseb = isset($row['generate_seb_exit_code']) ? (int)$row['generate_seb_exit_code'] : 1;
                $row['status'] = 'READY';
                $row['message'] = get_string('previewok', 'local_exammanager');
                $row['access_code'] = $generateaccess ? \local_exammanager\util::generate_code($usedcodes) : '';
                $row['seb_exit_code'] = $generateseb ? \local_exammanager\util::generate_code($usedcodes) : '';
            }
            unset($row);

            $_SESSION[$sessionkey] = $rows;
            $results = $rows;
            echo $OUTPUT->notification(get_string('readytoprogram', 'local_exammanager'), 'notifysuccess');
        } catch (Throwable $e) {
            echo $OUTPUT->notification($e->getMessage(), 'notifyproblem');
        }
    }
} else if ($action === 'program' && confirm_sesskey()) {
    if (!empty($_SESSION[$sessionkey])) {
        $rows = $_SESSION[$sessionkey];
        $usedcodes = [];
        foreach ($rows as &$row) {
            list($valid, $msg) = \local_exammanager\util::validate_row($row);
            if (!$valid) {
                $row['status'] = 'ERROR';
                $row['message'] = $msg;
                $row['access_code'] = '';
                $row['seb_exit_code'] = '';
                continue;
            }
            $outcome = \local_exammanager\manager::program_row($row, $usedcodes);
            $row['status'] = $outcome['status'];
            $row['message'] = $outcome['message'];
            $row['access_code'] = $outcome['access_code'] ?? '';
            $row['seb_exit_code'] = $outcome['seb_exit_code'] ?? '';
        }
        unset($row);

        $results = $rows;
        $_SESSION[$sessionkey] = $rows;

        try { \local_exammanager\exporter::export_csv($results, $csvexport); } catch (Throwable $e) {}
        try { \local_exammanager\exporter::export_excel($results, $excelexport); } catch (Throwable $e) {}
        try { \local_exammanager\exporter::export_log($results, $logexport); } catch (Throwable $e) {}
        try { \local_exammanager\exporter::export_pdf($results, $pdfexport); } catch (Throwable $e) {}

        $downloadlinks = [
            'csv' => new moodle_url('/local/exammanager/download.php', ['type' => 'csv']),
            'excel' => new moodle_url('/local/exammanager/download.php', ['type' => 'excel']),
            'pdf' => new moodle_url('/local/exammanager/download.php', ['type' => 'pdf']),
            'log' => new moodle_url('/local/exammanager/download.php', ['type' => 'log']),
        ];

        echo $OUTPUT->notification(get_string('processingdone', 'local_exammanager'), 'notifysuccess');
    } else {
        echo $OUTPUT->notification(get_string('nofile', 'local_exammanager'), 'notifyproblem');
    }
} else if (!empty($_SESSION[$sessionkey])) {
    $results = $_SESSION[$sessionkey];
}

if (!empty($results)) {
    echo $OUTPUT->heading(get_string('results', 'local_exammanager'), 3);

    $table = new html_table();
    $table->head = [
        get_string('course', 'local_exammanager'),
        get_string('quiz', 'local_exammanager'),
        get_string('open', 'local_exammanager'),
        get_string('close', 'local_exammanager'),
        get_string('duration', 'local_exammanager'),
        get_string('accesscode', 'local_exammanager'),
        get_string('sebexitcode', 'local_exammanager'),
        get_string('status', 'local_exammanager'),
        get_string('message', 'local_exammanager')
    ];

    foreach ($results as $row) {
        $table->data[] = [
            s($row['course_shortname'] ?? ''),
            s($row['quiz_name'] ?? ''),
            s($row['open_time'] ?? ''),
            s($row['close_time'] ?? ''),
            s((string)($row['time_limit'] ?? '')),
            s($row['access_code'] ?? ''),
            s($row['seb_exit_code'] ?? ''),
            s($row['status'] ?? ''),
            s($row['message'] ?? ''),
        ];
    }
    echo html_writer::table($table);

    if (array_reduce($results, function($carry, $row) {
        return $carry || (($row['status'] ?? '') === 'READY');
    }, false)) {
        echo html_writer::start_tag('form', ['method' => 'post']);
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'program']);
        echo html_writer::empty_tag('input', ['type' => 'submit', 'value' => get_string('programexams', 'local_exammanager')]);
        echo html_writer::end_tag('form');
    }

    if (!empty($downloadlinks)) {
        echo html_writer::start_div('mt-3');
        echo html_writer::link($downloadlinks['csv'], get_string('downloadcsv', 'local_exammanager')) . html_writer::empty_tag('br');
        echo html_writer::link($downloadlinks['excel'], get_string('downloadexcel', 'local_exammanager')) . html_writer::empty_tag('br');
        echo html_writer::link($downloadlinks['pdf'], get_string('downloadpdf', 'local_exammanager')) . html_writer::empty_tag('br');
        echo html_writer::link($downloadlinks['log'], get_string('downloadlog', 'local_exammanager')) . html_writer::empty_tag('br');
        echo html_writer::end_div();
    }
}

echo $OUTPUT->footer();
