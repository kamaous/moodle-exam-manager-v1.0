<?php
namespace local_exammanager;

defined('MOODLE_INTERNAL') || die();

class exporter {
    public static function export_csv(array $rows, string $filepath): void {
        $f = fopen($filepath, 'w');
        fputcsv($f, [
            'course_shortname','quiz_name','open_time','close_time','time_limit','teacher','room','session',
            'generate_access_code','generate_seb_exit_code','force_new_codes',
            'access_code','seb_exit_code','status','message'
        ], ';');

        foreach ($rows as $row) {
            fputcsv($f, [
                $row['course_shortname'] ?? '',
                $row['quiz_name'] ?? '',
                $row['open_time'] ?? '',
                $row['close_time'] ?? '',
                $row['time_limit'] ?? '',
                $row['teacher'] ?? '',
                $row['room'] ?? '',
                $row['session'] ?? '',
                $row['generate_access_code'] ?? '',
                $row['generate_seb_exit_code'] ?? '',
                $row['force_new_codes'] ?? '',
                $row['access_code'] ?? '',
                $row['seb_exit_code'] ?? '',
                $row['status'] ?? '',
                $row['message'] ?? ''
            ], ';');
        }
        fclose($f);
    }

    public static function export_excel(array $rows, string $filepath): void {
        $headers = [
            'course_shortname','quiz_name','open_time','close_time','time_limit','teacher','room','session',
            'generate_access_code','generate_seb_exit_code','force_new_codes',
            'access_code','seb_exit_code','status','message'
        ];

        $html = '<html><head><meta charset="utf-8"></head><body><table border="1" cellspacing="0" cellpadding="4">';
        $html .= '<tr>';
        foreach ($headers as $header) {
            $html .= '<th>' . s($header) . '</th>';
        }
        $html .= '</tr>';

        foreach ($rows as $row) {
            $html .= '<tr>';
            foreach ($headers as $header) {
                $html .= '<td>' . s($row[$header] ?? '') . '</td>';
            }
            $html .= '</tr>';
        }

        $html .= '</table></body></html>';
        file_put_contents($filepath, $html);
    }

    public static function export_log(array $rows, string $filepath): void {
        $lines = [];
        foreach ($rows as $row) {
            $lines[] = '[' . ($row['status'] ?? '') . '] '
                . ($row['course_shortname'] ?? '') . ' | '
                . ($row['quiz_name'] ?? '') . ' | '
                . ($row['open_time'] ?? '') . ' -> '
                . ($row['close_time'] ?? '') . ' | '
                . ($row['message'] ?? '');
        }
        file_put_contents($filepath, implode(PHP_EOL, $lines));
    }

    public static function export_pdf(array $rows, string $filepath): void {
        global $CFG;
        if (file_exists($CFG->dirroot . '/lib/tcpdf/tcpdf.php')) {
            require_once($CFG->dirroot . '/lib/tcpdf/tcpdf.php');
        }

        if (!class_exists('TCPDF')) {
            throw new \moodle_exception('TCPDF indisponible pour l’export PDF.');
        }

        $pdf = new \TCPDF();
        $pdf->SetCreator('Moodle');
        $pdf->SetAuthor('local_exammanager');
        $pdf->SetTitle('Codes surveillants');
        $pdf->SetMargins(15, 15, 15);
        $pdf->AddPage();
        $pdf->SetFont('helvetica', '', 10);

        $grouped = [];
        foreach ($rows as $row) {
            if (($row['status'] ?? '') !== 'PROGRAMMED') {
                continue;
            }
            $room = trim((string)($row['room'] ?? ''));
            if ($room === '') {
                $room = 'SALLE_NON_PRECISEE';
            }
            $grouped[$room][] = $row;
        }

        $html = '<h2>Codes surveillants</h2>';
        foreach ($grouped as $room => $items) {
            $html .= '<h3>Salle : ' . s($room) . '</h3>';
            $html .= '<table border="1" cellpadding="4"><tr><th>Quiz</th><th>Cours</th><th>Ouverture</th><th>Fermeture</th><th>Durée</th><th>Clé accès</th><th>Code sortie SEB</th></tr>';

            foreach ($items as $item) {
                $html .= '<tr>'
                    . '<td>' . s($item['quiz_name'] ?? '') . '</td>'
                    . '<td>' . s($item['course_shortname'] ?? '') . '</td>'
                    . '<td>' . s($item['open_time'] ?? '') . '</td>'
                    . '<td>' . s($item['close_time'] ?? '') . '</td>'
                    . '<td>' . s((string)($item['time_limit'] ?? '')) . '</td>'
                    . '<td>' . s($item['access_code'] ?? '') . '</td>'
                    . '<td>' . s($item['seb_exit_code'] ?? '') . '</td>'
                    . '</tr>';
            }

            $html .= '</table><br>';
        }

        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Output($filepath, 'F');
    }
}
