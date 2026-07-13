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

/**
 * Add the tour management link to the course navigation for tour managers.
 *
 * @param navigation_node $navigation Course navigation node to extend.
 * @param stdClass $course Course record.
 * @param context_course $context Course context.
 */
function local_unittours_extend_navigation_course(navigation_node $navigation, stdClass $course, context_course $context): void {
    if (!has_capability('local/unittours:manage', $context)) {
        return;
    }

    $url = new moodle_url('/local/unittours/manage.php', ['id' => $course->id]);
    $navigation->add(
        get_string('unittours', 'local_unittours'),
        $url,
        navigation_node::TYPE_SETTING,
        null,
        'local_unittours',
        new pix_icon('i/settings', '')
    );
}
