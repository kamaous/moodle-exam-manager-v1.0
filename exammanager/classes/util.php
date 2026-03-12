<?php
namespace local_exammanager;

defined('MOODLE_INTERNAL') || die();

class util {
    public static function tempdir(int $userid): string {
        return make_temp_directory('local_exammanager/' . $userid);
    }

    public static function tempfile(int $userid, string $name): string {
        return self::tempdir($userid) . '/' . $name;
    }

    public static function generate_code(array &$used): string {
        do {
            $code = (string)random_int(10000, 99999);
        } while (in_array($code, $used, true));
        $used[] = $code;
        return $code;
    }

    public static function parse_datetime(string $value): int {
        $dt = \DateTime::createFromFormat('Y-m-d H:i', trim($value));
        if (!$dt) {
            throw new \moodle_exception('Format de date invalide: ' . $value);
        }
        return $dt->getTimestamp();
    }

    public static function validate_row(array $row): array {
        $required = ['course_shortname', 'quiz_name', 'open_time', 'close_time', 'time_limit'];

        foreach ($required as $field) {
            if (!array_key_exists($field, $row) || trim((string)$row[$field]) === '') {
                return [false, 'Champ manquant: ' . $field];
            }
        }

        try {
            $open = self::parse_datetime((string)$row['open_time']);
            $close = self::parse_datetime((string)$row['close_time']);
            if ($close <= $open) {
                return [false, 'close_time doit être supérieur à open_time'];
            }
        } catch (\Throwable $e) {
            return [false, $e->getMessage()];
        }

        if (!is_numeric($row['time_limit']) || (int)$row['time_limit'] <= 0) {
            return [false, 'time_limit invalide'];
        }

        return [true, 'OK'];
    }
}
