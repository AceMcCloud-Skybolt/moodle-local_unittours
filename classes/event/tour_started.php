<?php
// This file is part of Moodle - http://moodle.org/

namespace local_unittours\event;

defined('MOODLE_INTERNAL') || die();

class tour_started extends \core\event\base {

    protected function init(): void {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'local_unittours_tours';
    }

    public static function get_name(): string {
        return get_string('event_tour_started', 'local_unittours');
    }

    public function get_description(): string {
        return "The user with id '{$this->userid}' started the unit tour with id '{$this->objectid}'.";
    }

    public function get_url(): \moodle_url {
        return new \moodle_url('/local/unittours/view.php', [
            'id' => $this->courseid,
            'tourid' => $this->objectid,
        ]);
    }

    public static function get_objectid_mapping(): array {
        return [
            'db' => 'local_unittours_tours',
            'restore' => 'local_unittours_tour',
        ];
    }
}
