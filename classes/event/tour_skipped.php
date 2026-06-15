<?php
// This file is part of Moodle - http://moodle.org/

namespace local_unittours\event;

defined('MOODLE_INTERNAL') || die();

class tour_skipped extends \core\event\base {

    protected function init(): void {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'local_unittours_completion';
    }

    public static function get_name(): string {
        return get_string('event_tour_skipped', 'local_unittours');
    }

    public function get_description(): string {
        return "The user with id '{$this->userid}' skipped the unit tour with id '{$this->other['tourid']}'.";
    }

    public function get_url(): \moodle_url {
        return new \moodle_url('/local/unittours/view.php', [
            'id' => $this->courseid,
            'tourid' => $this->other['tourid'],
        ]);
    }

    protected function validate_data(): void {
        parent::validate_data();

        if (!isset($this->other['tourid'])) {
            throw new \coding_exception('The tourid value must be set in other.');
        }
    }

    public static function get_objectid_mapping(): array {
        return [
            'db' => 'local_unittours_completion',
            'restore' => \core\event\base::NOT_MAPPED,
        ];
    }

    public static function get_other_mapping(): array {
        return [
            'tourid' => [
                'db' => 'local_unittours_tours',
                'restore' => 'local_unittours_tour',
            ],
        ];
    }
}
