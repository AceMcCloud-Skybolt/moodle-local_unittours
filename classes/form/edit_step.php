<?php
// This file is part of Moodle - http://moodle.org/

namespace local_unittours\form;

use local_unittours\local\target;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');

class edit_step extends \moodleform {

    protected function definition(): void {
        $mform = $this->_form;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'tourid');
        $mform->setType('tourid', PARAM_INT);

        $mform->addElement('hidden', 'stepid');
        $mform->setType('stepid', PARAM_INT);

        $mform->addElement('text', 'title', get_string('steptitle', 'local_unittours'), ['size' => 64]);
        $mform->setType('title', PARAM_TEXT);
        $mform->addRule('title', null, 'required', null, 'client');

        $mform->addElement('editor', 'content', get_string('stepcontent', 'local_unittours'), null, [
            'maxfiles' => 0,
            'trusttext' => false,
        ]);
        $mform->setType('content', PARAM_RAW);

        $mform->addElement('select', 'targettype', get_string('targettype', 'local_unittours'), [
            target::UNATTACHED => get_string('target_unattached', 'local_unittours'),
            target::COURSE_MODULE => get_string('target_course_module', 'local_unittours'),
            target::SECTION => get_string('target_section', 'local_unittours'),
            target::BLOCK => get_string('target_block', 'local_unittours'),
            target::COURSE_INDEX => get_string('target_course_index', 'local_unittours'),
            target::PAGE_REGION => get_string('target_page_region', 'local_unittours'),
            target::SELECTOR => get_string('target_selector', 'local_unittours'),
        ]);

        $mform->addElement('text', 'targetref', get_string('targetref', 'local_unittours'), ['size' => 64]);
        $mform->setType('targetref', PARAM_TEXT);
        $mform->addHelpButton('targetref', 'targetref', 'local_unittours');

        $mform->addElement('text', 'fallbackselector', get_string('fallbackselector', 'local_unittours'), ['size' => 64]);
        $mform->setType('fallbackselector', PARAM_RAW);
        $mform->addHelpButton('fallbackselector', 'fallbackselector', 'local_unittours');

        $mform->addElement('select', 'placement', get_string('placement', 'local_unittours'), [
            'top' => get_string('placement_top', 'local_unittours'),
            'bottom' => get_string('placement_bottom', 'local_unittours'),
            'left' => get_string('placement_left', 'local_unittours'),
            'right' => get_string('placement_right', 'local_unittours'),
        ]);

        $mform->addElement('advcheckbox', 'showiftargetmissing', get_string('showiftargetmissing', 'local_unittours'));
        $mform->addElement('advcheckbox', 'backdrop', get_string('backdrop', 'local_unittours'));

        $mform->addElement('advcheckbox', 'audioenabled', get_string('audioenabled', 'local_unittours'));
        $mform->addElement('advcheckbox', 'audioautoplay', get_string('audioautoplay', 'local_unittours'));
        $mform->disabledIf('audioautoplay', 'audioenabled', 'notchecked');

        $mform->addElement('textarea', 'audiotext', get_string('audiotext', 'local_unittours'), ['rows' => 4, 'cols' => 60]);
        $mform->setType('audiotext', PARAM_TEXT);
        $mform->addHelpButton('audiotext', 'audiotext', 'local_unittours');
        $mform->disabledIf('audiotext', 'audioenabled', 'notchecked');

        $mform->addElement('text', 'audiolang', get_string('audiolang', 'local_unittours'), ['size' => 24]);
        $mform->setType('audiolang', PARAM_ALPHANUMEXT);
        $mform->addHelpButton('audiolang', 'audiolang', 'local_unittours');
        $mform->disabledIf('audiolang', 'audioenabled', 'notchecked');

        $mform->addElement('advcheckbox', 'audioenaustralian', get_string('audioenaustralian', 'local_unittours'));
        $mform->disabledIf('audioenaustralian', 'audioenabled', 'notchecked');

        $this->add_action_buttons(true, get_string('savechanges'));
    }
}
