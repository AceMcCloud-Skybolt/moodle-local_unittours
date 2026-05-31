<?php
// This file is part of Moodle - http://moodle.org/

namespace local_unittours\form;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');

class edit_tour extends \moodleform {

    protected function definition(): void {
        $mform = $this->_form;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'tourid');
        $mform->setType('tourid', PARAM_INT);

        $mform->addElement('text', 'name', get_string('tourname', 'local_unittours'), ['size' => 64]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

        $mform->addElement('editor', 'description', get_string('description'), null, [
            'maxfiles' => 0,
            'trusttext' => false,
        ]);
        $mform->setType('description', PARAM_RAW);

        $mform->addElement('advcheckbox', 'enabled', get_string('enabled', 'local_unittours'));

        $mform->addElement('select', 'audience', get_string('audience', 'local_unittours'), [
            'student' => get_string('audience_student', 'local_unittours'),
            'staff' => get_string('audience_staff', 'local_unittours'),
            'all' => get_string('audience_all', 'local_unittours'),
        ]);

        $mform->addElement('select', 'showmode', get_string('showmode', 'local_unittours'), [
            'untilcomplete' => get_string('showmode_untilcomplete', 'local_unittours'),
            'always' => get_string('showmode_always', 'local_unittours'),
        ]);

        $this->add_action_buttons(true, get_string('savechanges'));
    }
}
