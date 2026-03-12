<?php
namespace local_exammanager;

defined('MOODLE_INTERNAL') || die();

require_once($GLOBALS['CFG']->dirroot . '/course/modlib.php');

class manager {
    public static function program_row(array $row, array &$usedcodes): array {
        global $DB;

        $course = $DB->get_record('course', ['shortname' => trim((string)$row['course_shortname'])], '*', IGNORE_MISSING);
        if (!$course) {
            return ['status' => 'ERROR', 'message' => 'Course not found: ' . $row['course_shortname']];
        }

        $quiz = $DB->get_record('quiz', [
            'course' => $course->id,
            'name' => trim((string)$row['quiz_name'])
        ], '*', IGNORE_MISSING);
        if (!$quiz) {
            return ['status' => 'ERROR', 'message' => 'Quiz not found in course: ' . $row['quiz_name']];
        }

        $cm = get_coursemodule_from_instance('quiz', $quiz->id, $course->id, false, MUST_EXIST);

        $generateaccess = isset($row['generate_access_code']) ? (int)$row['generate_access_code'] : 1;
        $generateseb = isset($row['generate_seb_exit_code']) ? (int)$row['generate_seb_exit_code'] : 1;
        $force = !empty($row['force_new_codes']) && (int)$row['force_new_codes'] === 1;

        $existinglocal = $DB->get_record('local_exammanager_codes', ['quizid' => $quiz->id], '*', IGNORE_MISSING);

        $accesscode = '';
        $sebexitcode = '';

        if ($generateaccess) {
            $accesscode = $force
                ? util::generate_code($usedcodes)
                : (($existinglocal && !empty($existinglocal->access_code)) ? $existinglocal->access_code : util::generate_code($usedcodes));
        }

        if ($generateseb) {
            $sebexitcode = $force
                ? util::generate_code($usedcodes)
                : (($existinglocal && !empty($existinglocal->seb_exit_code)) ? $existinglocal->seb_exit_code : util::generate_code($usedcodes));
        }

        $timeopen = util::parse_datetime((string)$row['open_time']);
        $timeclose = util::parse_datetime((string)$row['close_time']);
        $timelimit = ((int)$row['time_limit']) * 60;

        $transaction = $DB->start_delegated_transaction();
        try {
            $quizupdate = new \stdClass();
            $quizupdate->id = $quiz->id;
            $quizupdate->timeopen = $timeopen;
            $quizupdate->timeclose = $timeclose;
            $quizupdate->timelimit = $timelimit;
            if ($accesscode !== '') {
                $quizupdate->password = $accesscode;
            }
            $DB->update_record('quiz', $quizupdate);

            $rec = new \stdClass();
            $rec->quizid = $quiz->id;
            $rec->courseid = $course->id;
            $rec->quizname = $quiz->name;
            $rec->course_shortname = $course->shortname;
            $rec->access_code = $accesscode;
            $rec->seb_exit_code = $sebexitcode;
            $rec->teacher = trim((string)($row['teacher'] ?? ''));
            $rec->room = trim((string)($row['room'] ?? ''));
            $rec->sessionname = trim((string)($row['session'] ?? ''));
            $rec->timeopen = $timeopen;
            $rec->timeclose = $timeclose;
            $rec->timelimit = $timelimit;
            $rec->timemodified = time();

            if ($existinglocal) {
                $rec->id = $existinglocal->id;
                $DB->update_record('local_exammanager_codes', $rec);
            } else {
                $DB->insert_record('local_exammanager_codes', $rec);
            }

            $manager = $DB->get_manager();
            $sebtable = new \xmldb_table('quizaccess_seb_quizsettings');
            if ($sebexitcode !== '' && $manager->table_exists($sebtable)) {
                $existingseb = $DB->get_record('quizaccess_seb_quizsettings', ['cmid' => $cm->id], '*', IGNORE_MISSING);
                $cols = $DB->get_columns('quizaccess_seb_quizsettings');

                $seb = new \stdClass();
                if (isset($cols['cmid'])) $seb->cmid = $cm->id;
                if (isset($cols['quizid'])) $seb->quizid = $quiz->id;
                if (isset($cols['quitpassword'])) $seb->quitpassword = $sebexitcode;
                if (isset($cols['templateid'])) $seb->templateid = 0;
                if (isset($cols['requiresafeexambrowser'])) $seb->requiresafeexambrowser = 1;
                if (isset($cols['showsebdownloadlink'])) $seb->showsebdownloadlink = 1;
                if (isset($cols['linkquitseb'])) $seb->linkquitseb = 0;
                if (isset($cols['userconfirmquit'])) $seb->userconfirmquit = 1;

                if ($existingseb) {
                    $seb->id = $existingseb->id;
                    $DB->update_record('quizaccess_seb_quizsettings', $seb);
                } else {
                    $DB->insert_record('quizaccess_seb_quizsettings', $seb);
                }
            }

            $transaction->allow_commit();
            return [
                'status' => 'PROGRAMMED',
                'message' => get_string('programok', 'local_exammanager'),
                'access_code' => $accesscode,
                'seb_exit_code' => $sebexitcode
            ];
        } catch (\Throwable $e) {
            $transaction->rollback($e);
            return [
                'status' => 'ERROR',
                'message' => $e->getMessage(),
                'access_code' => $accesscode,
                'seb_exit_code' => $sebexitcode
            ];
        }
    }
}
