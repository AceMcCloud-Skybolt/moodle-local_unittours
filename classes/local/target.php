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

/**
 * Constants for the step target types supported by the tour player.
 *
 * @package    local_unittours
 * @copyright  2026 Murdoch University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class target {
    /** @var string Step is not attached to any page element. */
    public const UNATTACHED = 'unattached';
    /** @var string Step targets an activity or resource (targetref is a cmid). */
    public const COURSE_MODULE = 'course_module';

    /** @var string Step targets a course section (targetref is a section id). */
    public const SECTION = 'section';

    /** @var string Step targets a block instance (targetref is a block name). */
    public const BLOCK = 'block';

    /** @var string Step targets an item in the course navigation (targetref is a nav key). */
    public const COURSE_NAVIGATION = 'course_navigation';

    /** @var string Step targets a theme page region (targetref is a region name). */
    public const PAGE_REGION = 'page_region';

    /** @var string Step targets an arbitrary CSS selector. */
    public const SELECTOR = 'selector';
}
