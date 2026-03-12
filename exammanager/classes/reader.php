<?php
namespace local_exammanager;

defined('MOODLE_INTERNAL') || die();

class reader {
    public static function read_rows(string $filepath): array {
        global $CFG;

        $ext = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));

        if ($ext === 'csv') {
            $fh = fopen($filepath, 'r');
            if (!$fh) {
                throw new \moodle_exception('Impossible de lire le fichier CSV');
            }

            $header = fgetcsv($fh, 0, ';');
            if (!$header) {
                fclose($fh);
                throw new \moodle_exception('CSV vide');
            }

            $header = array_map('trim', $header);
            $rows = [];

            while (($data = fgetcsv($fh, 0, ';')) !== false) {
                if (count(array_filter($data, fn($v) => trim((string)$v) !== '')) === 0) {
                    continue;
                }
                $rows[] = array_combine($header, $data);
            }

            fclose($fh);
            return $rows;
        }

        if ($ext === 'xlsx' || $ext === 'xls') {
            if (file_exists($CFG->dirroot . '/lib/phpspreadsheet/vendor/autoload.php')) {
                require_once($CFG->dirroot . '/lib/phpspreadsheet/vendor/autoload.php');
            } else if (file_exists($CFG->dirroot . '/vendor/autoload.php')) {
                require_once($CFG->dirroot . '/vendor/autoload.php');
            }

            if (!class_exists('\PhpOffice\PhpSpreadsheet\IOFactory')) {
                throw new \moodle_exception('PhpSpreadsheet indisponible sur cette plateforme.');
            }

            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filepath);
            $sheet = $spreadsheet->getActiveSheet();
            $raw = $sheet->toArray(null, true, true, false);
            if (empty($raw)) {
                throw new \moodle_exception('Fichier Excel vide');
            }

            $header = array_map('trim', $raw[0]);
            $rows = [];
            for ($i = 1; $i < count($raw); $i++) {
                $line = $raw[$i];
                if (count(array_filter($line, fn($v) => trim((string)$v) !== '')) === 0) {
                    continue;
                }
                $rows[] = array_combine($header, $line);
            }
            return $rows;
        }

        throw new \moodle_exception(get_string('invalidfile', 'local_exammanager'));
    }
}
