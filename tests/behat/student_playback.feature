@local @local_unittours @javascript
Feature: Student unit tour playback anchors to Moodle course page elements
  In order to guide students to important unit areas
  As a teacher
  I need unit tour steps to attach to the intended live course page element

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Student   | One      | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname | format | numsections |
      | Course 1 | C1        | topics | 3           |
    And the following "activities" exist:
      | activity | name             | intro                    | course | idnumber | section |
      | assign   | First assignment | Assignment introduction. | C1     | assign1  | 1       |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | C1     | student |

  Scenario: A section-targeted tour step highlights the intended section
    Given a section unit tour exists in course "C1" targeting section "1"
    When I am on the "C1" "course" page logged in as "student1"
    Then I should see the unit tour popover "Section tour step"
    And the unit tour should highlight section "1" in course "C1"

  Scenario: A course navigation tour step highlights Grades
    Given a course navigation unit tour exists in course "C1" targeting "grades"
    When I am on the "C1" "course" page logged in as "student1"
    Then I should see the unit tour popover "Navigation tour step"
    And the unit tour should highlight the course navigation item "grades"
