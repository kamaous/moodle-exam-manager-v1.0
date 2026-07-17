<?php
namespace local_exammanager\output;

defined('MOODLE_INTERNAL') || die();

class navbar {
    public static function render(string $active = 'dashboard'): string {
        $items = [
            'dashboard' => [get_string('dashboard', 'local_exammanager'), new \moodle_url('/local/exammanager/dashboard.php')],
            'programming' => [get_string('planning', 'local_exammanager'), new \moodle_url('/local/exammanager/index.php')],
            'activities' => [get_string('activitiesplanning', 'local_exammanager'), new \moodle_url('/local/exammanager/activities.php')],
            'calendar' => [get_string('calendar', 'local_exammanager'), new \moodle_url('/local/exammanager/calendar.php')],
            'history' => [get_string('history', 'local_exammanager'), new \moodle_url('/local/exammanager/history.php')],
            'sessions' => [get_string('sessions', 'local_exammanager'), new \moodle_url('/local/exammanager/sessions.php')],
            'reports' => [get_string('reports', 'local_exammanager'), new \moodle_url('/local/exammanager/reports.php')],
        ];

        $html = '<div class="local-exammanager-nav">';
        foreach ($items as $key => $item) {
            $class = $key === $active ? 'active' : '';
            $html .= \html_writer::link($item[1], $item[0], ['class' => $class]);
        }
        $html .= '</div>';
        return $html;
    }
}
