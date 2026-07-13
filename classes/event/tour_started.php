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

/**
 * Event triggered when a user starts a unit tour.
 *
 * @package    local_unittours
 * @copyright  2026 Murdoch University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tour_started extends \core\event\base {
    /**
     * Initialise the event data.
     */
    protected function init(): void {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'local_unittours_tours';
    }

    /**
     * Get the localised event name.
     *
     * @return string Event name.
     */
    public static function get_name(): string {
        return get_string('event_tour_started', 'local_unittours');
    }

    /**
     * Get the non-localised event description.
     *
     * @return string Event description.
     */
    public function get_description(): string {
        return "The user with id '{$this->userid}' started the unit tour with id '{$this->objectid}'.";
    }

    /**
     * Get the URL related to this event.
     *
     * @return \moodle_url Tour view URL.
     */
    public function get_url(): \moodle_url {
        return new \moodle_url('/local/unittours/view.php', [
            'id' => $this->courseid,
            'tourid' => $this->objectid,
        ]);
    }

    /**
     * Describe how objectid is mapped during backup and restore.
     *
     * @return array Mapping definition.
     */
    public static function get_objectid_mapping(): array {
        return [
            'db' => 'local_unittours_tours',
            'restore' => 'local_unittours_tour',
        ];
    }
}
