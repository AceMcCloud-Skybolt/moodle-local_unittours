<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Unit tours plugin.
 *
 * @package    local_unittours
 * @copyright  2026 Murdoch University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

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
