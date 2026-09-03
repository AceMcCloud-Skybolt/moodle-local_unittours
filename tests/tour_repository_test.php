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
 * Tests for the Unit tours repository.
 *
 * @package    local_unittours
 * @category   test
 * @copyright  2026 Murdoch University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_unittours;

use advanced_testcase;
use local_unittours\local\target;
use local_unittours\local\tour_repository;

/**
 * Repository integration tests.
 *
 * @covers \local_unittours\local\tour_repository
 */
final class tour_repository_test extends advanced_testcase {
    /**
     * Step moves respect boundaries and swap adjacent positions.
     */
    public function test_move_step_boundaries_and_reordering(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $tourid = $this->create_tour((int) $course->id);
        $first = $this->create_step($tourid, (int) $course->id, 'First');
        $second = $this->create_step($tourid, (int) $course->id, 'Second');
        $third = $this->create_step($tourid, (int) $course->id, 'Third');

        tour_repository::move_step($first, (int) $course->id, 'up');
        tour_repository::move_step($third, (int) $course->id, 'down');
        $this->assertSame(['First', 'Second', 'Third'], $this->step_titles($tourid));

        tour_repository::move_step($third, (int) $course->id, 'up');
        $this->assertSame(['First', 'Third', 'Second'], $this->step_titles($tourid));
        $this->assertSame($tourid, tour_repository::move_step($second, (int) $course->id, 'down'));
    }

    /**
     * Completion writes are idempotent and reject unknown statuses.
     */
    public function test_mark_completion_is_idempotent_and_validated(): void {
        global $DB;

        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $tourid = $this->create_tour((int) $course->id);

        tour_repository::mark_completion($tourid, (int) $course->id, (int) $user->id, 'skipped');
        tour_repository::mark_completion($tourid, (int) $course->id, (int) $user->id, 'complete');

        $this->assertEquals(1, $DB->count_records('local_unittours_completion', [
            'tourid' => $tourid,
            'userid' => $user->id,
        ]));
        $this->assertEquals('complete', $DB->get_field('local_unittours_completion', 'status', [
            'tourid' => $tourid,
            'userid' => $user->id,
        ]));

        $this->expectException(\invalid_parameter_exception::class);
        tour_repository::mark_completion($tourid, (int) $course->id, (int) $user->id, 'completed');
    }

    /**
     * Group tours are visible only to matching students, while staff preview sees all.
     */
    public function test_group_audience_filtering(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_user();
        $groupa = $this->getDataGenerator()->create_group(['courseid' => $course->id]);
        $groupb = $this->getDataGenerator()->create_group(['courseid' => $course->id]);
        groups_add_member($groupa, $student);

        $matchingtour = $this->create_tour((int) $course->id, 'Matching group', [(int) $groupa->id]);
        $this->create_tour((int) $course->id, 'Other group', [(int) $groupb->id]);

        $studenttours = tour_repository::get_enabled_tours_for_course(
            (int) $course->id,
            'student',
            (int) $student->id
        );
        $this->assertSame([$matchingtour], array_map('intval', array_keys($studenttours)));

        $stafftours = tour_repository::get_enabled_tours_for_course((int) $course->id, 'staff', (int) $student->id);
        $this->assertCount(2, $stafftours);
    }

    /**
     * Create a tour suitable for repository tests.
     *
     * @param int $courseid Course id.
     * @param string $name Tour name.
     * @param int[] $groupids Optional audience group ids.
     * @return int Tour id.
     */
    private function create_tour(int $courseid, string $name = 'Test tour', array $groupids = []): int {
        return tour_repository::save_tour((object) [
            'name' => $name,
            'description' => ['text' => '', 'format' => FORMAT_HTML],
            'enabled' => 1,
            'audience' => $groupids ? 'group' : 'all',
            'groupids' => $groupids,
            'showmode' => 'untilcomplete',
        ], $courseid);
    }

    /**
     * Create a basic unattached step.
     *
     * @param int $tourid Tour id.
     * @param int $courseid Course id.
     * @param string $title Step title.
     * @return int Step id.
     */
    private function create_step(int $tourid, int $courseid, string $title): int {
        return tour_repository::save_step((object) [
            'tourid' => $tourid,
            'title' => $title,
            'content' => ['text' => '', 'format' => FORMAT_HTML],
            'targettype' => target::UNATTACHED,
            'targetref' => '',
            'fallbackselector' => '',
            'placement' => 'bottom',
            'showiftargetmissing' => 1,
            'backdrop' => 0,
            'audioenabled' => 0,
            'audioautoplay' => 0,
            'audiotext' => '',
            'audiolang' => '',
        ], $courseid);
    }

    /**
     * Return step titles in display order.
     *
     * @param int $tourid Tour id.
     * @return string[] Titles.
     */
    private function step_titles(int $tourid): array {
        return array_values(array_map(
            static fn(\stdClass $step): string => $step->title,
            tour_repository::get_steps_for_tour($tourid)
        ));
    }
}
