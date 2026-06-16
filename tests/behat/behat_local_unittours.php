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

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

use Behat\Mink\Exception\ExpectationException;

/**
 * Behat steps for local_unittours.
 *
 * @package    local_unittours
 * @category   test
 * @copyright  2026 Murdoch University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_local_unittours extends behat_base {

    /**
     * Creates a one-step section-targeted unit tour.
     *
     * @Given /^a section unit tour exists in course "(?P<course>[^"]*)" targeting section "(?P<sectionnumber>\d+)"$/
     *
     * @param string $courseshortname Course shortname.
     * @param int $sectionnumber Section number.
     */
    public function a_section_unit_tour_exists_in_course_targeting_section(
        string $courseshortname,
        int $sectionnumber
    ): void {
        global $DB;

        $course = $DB->get_record('course', ['shortname' => $courseshortname], '*', MUST_EXIST);
        $section = $DB->get_record('course_sections', [
            'course' => $course->id,
            'section' => $sectionnumber,
        ], '*', MUST_EXIST);

        $this->create_tour_with_step(
            (int) $course->id,
            'Section smoke tour',
            'Section tour step',
            'This popover should be anchored to the requested section.',
            \local_unittours\local\target::SECTION,
            (string) $section->id,
            '[data-sectionid="' . $section->id . '"], [data-for="section"][data-id="' . $section->id . '"]'
        );
    }

    /**
     * Creates a one-step course-navigation-targeted unit tour.
     *
     * @Given /^a course navigation unit tour exists in course "(?P<course>[^"]*)" targeting "(?P<targetref>[^"]*)"$/
     *
     * @param string $courseshortname Course shortname.
     * @param string $targetref Navigation target key.
     */
    public function a_course_navigation_unit_tour_exists_in_course_targeting(
        string $courseshortname,
        string $targetref
    ): void {
        global $DB;

        $course = $DB->get_record('course', ['shortname' => $courseshortname], '*', MUST_EXIST);

        $this->create_tour_with_step(
            (int) $course->id,
            'Navigation smoke tour',
            'Navigation tour step',
            'This popover should be anchored to the requested course navigation item.',
            \local_unittours\local\target::COURSE_NAVIGATION,
            $targetref
        );
    }

    /**
     * Checks that a unit tour popover is visible with the expected title.
     *
     * @Then /^I should see the unit tour popover "(?P<title>[^"]*)"$/
     *
     * @param string $title Expected popover title.
     */
    public function i_should_see_the_unit_tour_popover(string $title): void {
        $session = $this->getSession();
        $exception = new ExpectationException('The unit tour popover was not visible.', $session);

        $this->spin(function () use ($title) {
            $popover = $this->getSession()->getPage()->find('css', '.local-unittours-popover');
            if (!$popover) {
                return false;
            }

            $heading = $popover->find('css', 'h3');
            return $heading && trim($heading->getText()) === $title;
        }, false, false, $exception);
    }

    /**
     * Checks that the active unit tour highlight is attached to a course section.
     *
     * @Then /^the unit tour should highlight section "(?P<sectionnumber>\d+)" in course "(?P<course>[^"]*)"$/
     *
     * @param int $sectionnumber Section number.
     * @param string $courseshortname Course shortname.
     */
    public function the_unit_tour_should_highlight_section_in_course(int $sectionnumber, string $courseshortname): void {
        global $DB;

        $course = $DB->get_record('course', ['shortname' => $courseshortname], '*', MUST_EXIST);
        $section = $DB->get_record('course_sections', [
            'course' => $course->id,
            'section' => $sectionnumber,
        ], '*', MUST_EXIST);

        $selector = '[data-sectionid="' . $section->id . '"], [data-for="section"][data-id="' . $section->id . '"]';
        $this->highlight_matches_selector($selector, 'The unit tour did not highlight the expected section.');
    }

    /**
     * Checks that the active unit tour highlight is attached to a course navigation item.
     *
     * @Then /^the unit tour should highlight the course navigation item "(?P<targetref>[^"]*)"$/
     *
     * @param string $targetref Navigation target key.
     */
    public function the_unit_tour_should_highlight_the_course_navigation_item(string $targetref): void {
        $targetrefjson = json_encode($targetref);
        $script = <<<JS
const normalise = text => (text || '').trim().toLowerCase().replace(/[^a-z ]/g, '').replace(/\s+/g, '_');
const highlighted = document.querySelector('.local-unittours-highlight');
if (!highlighted) {
    return false;
}
return Array.from(document.querySelectorAll('.secondary-navigation a, .secondary-navigation button, [role="menuitem"]'))
    .some(candidate => candidate === highlighted && normalise(candidate.textContent) === {$targetrefjson});
JS;

        $this->assert_js_returns_true(
            $script,
            'The unit tour did not highlight the expected course navigation item.'
        );
    }

    /**
     * Creates a tour with a single step.
     *
     * @param int $courseid Course id.
     * @param string $tourname Tour name.
     * @param string $steptitle Step title.
     * @param string $stepcontent Step content.
     * @param string $targettype Target type.
     * @param string $targetref Target reference.
     * @param string|null $fallbackselector Fallback selector.
     */
    private function create_tour_with_step(
        int $courseid,
        string $tourname,
        string $steptitle,
        string $stepcontent,
        string $targettype,
        string $targetref,
        ?string $fallbackselector = null
    ): void {
        $tourid = \local_unittours\local\tour_repository::save_tour((object) [
            'name' => $tourname,
            'description' => [
                'text' => '<p>Behat smoke test tour.</p>',
                'format' => FORMAT_HTML,
            ],
            'enabled' => 1,
            'audience' => 'all',
            'showmode' => 'untilcomplete',
        ], $courseid);

        \local_unittours\local\tour_repository::save_step((object) [
            'tourid' => $tourid,
            'title' => $steptitle,
            'content' => [
                'text' => '<p>' . s($stepcontent) . '</p>',
                'format' => FORMAT_HTML,
            ],
            'targettype' => $targettype,
            'targetref' => $targetref,
            'fallbackselector' => $fallbackselector,
            'placement' => 'bottom',
            'showiftargetmissing' => 0,
            'backdrop' => 0,
            'audioenabled' => 0,
            'audioautoplay' => 0,
            'audiotext' => '',
            'audiolang' => '',
        ], $courseid);
    }

    /**
     * Checks that the active highlight matches the supplied CSS selector.
     *
     * @param string $selector Expected selector.
     * @param string $message Failure message.
     */
    private function highlight_matches_selector(string $selector, string $message): void {
        $script = <<<JS
const expected = document.querySelector('{$selector}');
const highlighted = document.querySelector('.local-unittours-highlight');
return !!expected && highlighted === expected;
JS;

        $this->assert_js_returns_true($script, $message);
    }

    /**
     * Waits until the supplied JavaScript returns true.
     *
     * @param string $script JavaScript body.
     * @param string $message Failure message.
     */
    private function assert_js_returns_true(string $script, string $message): void {
        $session = $this->getSession();
        $exception = new ExpectationException($message, $session);

        $this->spin(function () use ($script) {
            return $this->getSession()->evaluateScript('(function() {' . $script . '})()') === true;
        }, false, false, $exception);
    }
}
