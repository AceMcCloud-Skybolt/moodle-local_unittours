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

namespace local_unittours\local;

defined('MOODLE_INTERNAL') || die();

final class target {
    public const UNATTACHED = 'unattached';
    public const COURSE_MODULE = 'course_module';
    public const SECTION = 'section';
    public const BLOCK = 'block';
    public const COURSE_NAVIGATION = 'course_navigation';
    public const PAGE_REGION = 'page_region';
    public const SELECTOR = 'selector';
}
