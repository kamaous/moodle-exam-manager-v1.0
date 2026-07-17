<?php
namespace local_exammanager;

defined('MOODLE_INTERNAL') || die();

class hook_callbacks {

    /**
     * Ajoute un accès rapide au plugin dans la barre de navigation principale,
     * uniquement pour les utilisateurs autorisés (gestionnaires et administrateurs).
     */
    public static function primary_extend(\core\hook\navigation\primary_extend $hook): void {
        if (!isloggedin() || isguestuser()) {
            return;
        }

        if (!has_capability('local/exammanager:manage', \context_system::instance())) {
            return;
        }

        $hook->get_primaryview()->add(
            get_string('primarynavlabel', 'local_exammanager'),
            new \moodle_url('/local/exammanager/dashboard.php'),
            \navigation_node::TYPE_CUSTOM,
            null,
            'localexammanager'
        );
    }
}
