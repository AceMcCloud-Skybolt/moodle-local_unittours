# Moodle 5.1 upgrade UAT: Unit Tours

Record the Moodle build, plugin version, Git commit, theme, course format, browser/device, tester and date. Mark each check Pass, Fail or Blocked and attach evidence for failures. Allow approximately 30-45 minutes for the web checks, plus app and rollover checks.

## Test setup

- Use the upgrade/UAT environment and a disposable course with two sections, a Page activity, an Announcements forum and a course-level block.
- Enrol an editing teacher and two students. Put one student in Internal and the other in External, with no overlapping membership. Keep a second course for access-boundary checks.
- Test the actual Murdoch theme and course format. Repeat targeting checks in Boost if the institutional theme behaves differently.
- Install the plugin in `public/local/unittours` for Moodle 5.1, run the Moodle upgrade and purge caches. Confirm release 0.2.0 / version 2026090300 in the plugin overview.
- Use separate browser profiles for the teacher and students so completion and permissions are exercised with real accounts.

## Smoke checks

| ID | Action | Expected result | Result / evidence |
| --- | --- | --- | --- |
| 01 | Open the course as editing teacher and choose Unit tours from More. | Management page opens without errors or untranslated labels. | |
| 02 | Create a disabled tour, save its name and description, then reopen it. | Settings persist; neither student sees an automatic tour while disabled. | |
| 03 | Add steps for section 1, the Page activity, the course block and Grades using the visual picker where available. Reorder them and preview. | Correct objects highlight in the chosen order; titles and content render correctly, including `Q&A`. | |
| 04 | Enable the tour for everyone and use Show until completed. Visit as both students. | Both see the tour; student-facing navigation targets point to the student's Grades link. | |
| 05 | Complete as student A, reload and revisit. Relaunch manually, then reset that student's completion. | Completion suppresses automatic replay; manual replay works; reset permits automatic replay. Student B's state is unaffected. | |
| 06 | Skip as student B and revisit. Check Show every visit separately. | Skip is recorded and suppresses automatic replay in until-completed mode; every-visit mode replays on a fresh visit. | |
| 07 | Restrict a tour to Internal, then External. Test both students and staff preview. | Only the matching student can play each restricted tour; staff can preview. | |
| 08 | Attempt the management URL as a student, and a tour URL belonging to the other course without enrolment. | Access is denied and restricted content is not exposed. | |
| 09 | Create an unattached urgent-news step and preview/enable it. | Readable modal appears without needing a page target; dismissal and completion work. | |
| 10 | Remove a target in this disposable course. Check target health and playback with Show if target missing on and off. | Health flags the missing object; enabled fallback shows unattached content, disabled fallback skips the missing step without trapping the learner. | |
| 11 | Add audio text and test manual playback, Stop, advancing and dismissing. Test autoplay where permitted and blocked. | Text remains readable; manual playback works where supported; speech stops when leaving the step; blocked autoplay does not prevent tour use. | |
| 12 | Use keyboard only: launch, Tab/Shift+Tab, Next, Back and dismiss. Zoom to 200%. | Focus remains usable, returns sensibly after dismissal, and controls/content remain readable without overlap. | |
| 13 | Repeat activity/section/navigation/modal playback on a narrow mobile browser and with the block drawer closed/open. | Dialog stays within the viewport and target behavior is usable. Record drawer/theme limitations. | |
| 14 | Inspect Moodle logs after start, skip and completion. | Unit Tours started, skipped and completed events appear for the correct course/user. | |
| 15 | Back up and restore/copy the course with its tours, activities, sections and audience groups. Test as newly enrolled learners. | Tours and order survive; object references point into the new course; groups map correctly; old completion does not suppress the new course tour. Recheck block targets and repair any flagged target. | |
| 16 | Open the course in the real Moodle app on Android and iOS. Test launch, targeting, modal, completion and audio where available. | Record actual support and limitations. Responsive web success alone does not establish app support; students must retain access to essential information outside the tour. | |

## Release decision

Resolve failures in installation, permissions, group filtering, completion, essential targeting and rollover before production approval. Record browser/theme/app limitations and their agreed alternatives. Keep urgent or essential messages in an accessible course resource or Announcements as well as in a tour.

Approver: __________ Date: __________ Commit tested: __________

## Existing validation evidence

[September validation record](release-validation-2026-09-03.md): Moodle 5.1.4+, PHP 8.2.4, MariaDB 10.11.11; PHP lint, PHPUnit, target/event smoke and controlled backup/restore passed. Behat initialized and dry-run passed, but JavaScript scenarios did not execute because WebDriver was unavailable. Browser and app acceptance remain required on the upgrade environment.
