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

defined('MOODLE_INTERNAL') || die();

class backup_local_unittours_plugin extends backup_local_plugin {

    protected function define_course_plugin_structure() {
        $plugin = $this->get_plugin_element();
        $pluginwrapper = new backup_nested_element($this->get_recommended_name());
        $plugin->add_child($pluginwrapper);

        $tours = new backup_nested_element('unit_tours');
        $tour = new backup_nested_element('unit_tour', ['id'], [
            'name',
            'description',
            'descriptionformat',
            'enabled',
            'audience',
            'showmode',
            'sortorder',
            'timecreated',
            'timemodified',
        ]);

        $steps = new backup_nested_element('unit_tour_steps');
        $step = new backup_nested_element('unit_tour_step', ['id'], [
            'title',
            'content',
            'contentformat',
            'targettype',
            'targetref',
            'fallbackselector',
            'placement',
            'showiftargetmissing',
            'backdrop',
            'audioenabled',
            'audioautoplay',
            'audiotext',
            'audiolang',
            'sortorder',
            'timecreated',
            'timemodified',
        ]);
        $groups = new backup_nested_element('unit_tour_groups');
        $group = new backup_nested_element('unit_tour_group', ['id'], [
            'groupid',
        ]);

        $pluginwrapper->add_child($tours);
        $tours->add_child($tour);
        $tour->add_child($groups);
        $groups->add_child($group);
        $tour->add_child($steps);
        $steps->add_child($step);

        $tour->set_source_table(
            'local_unittours_tours',
            ['courseid' => backup::VAR_COURSEID],
            'sortorder ASC, id ASC'
        );
        $step->set_source_table(
            'local_unittours_steps',
            ['tourid' => backup::VAR_PARENTID],
            'sortorder ASC, id ASC'
        );
        $group->set_source_table(
            'local_unittours_tour_groups',
            ['tourid' => backup::VAR_PARENTID],
            'id ASC'
        );

        return $plugin;
    }
}
