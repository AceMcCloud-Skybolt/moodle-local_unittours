# Moodle 5.1 compatibility review

Review date: 9 June 2026

Local Moodle tested: 5.1.4+ (Build: 20260604), branch 501

## Result

`local_unittours` is broadly compatible with Moodle 5.1 in the upgraded local instance.

The plugin installs in the new Moodle 5.1 `public/local/unittours` location, database schema checks pass, course navigation is visible, the management screens load, course-page playback works, and the visual target picker loads.

## Checks completed

- Confirmed the plugin is deployed under `D:\server\moodle\public\local\unittours`.
- Ran Moodle CLI upgrade check: no Unit Tours upgrade errors.
- Ran Moodle database schema check: database structure reported OK.
- Ran PHP lint across all plugin PHP files: no syntax errors.
- Searched for Moodle 5.1 deprecation touchpoints listed in release/developer notes:
  - no `custom_chooser_footer`
  - no `core_course/activitychooser` overrides
  - no `core_course_renderer::course_activitychooser`
  - no `file_encode_url`
  - no removed badge renderer/device theme callbacks
  - no YUI dependency
- Browser-tested:
  - `manage.php?id=2`
  - `view.php?id=2&tourid=1`
  - `course/view.php?id=2`
  - target picker mode from the step editor flow
- Checked browser console during playback/picker smoke test: no errors or warnings observed.

## Moodle 5.1 notes relevant to this plugin

- Moodle 5.1 moves most web-facing Moodle code, including plugins, into the `public` directory.
- Activity chooser logic moved from `core_course` to `core_courseformat`, but this plugin does not override activity chooser templates, renderers, or custom footers.
- Moodle 5.1 adds an `Activities` course navigation item. Unit Tours still appears in the course More menu.

## Findings

### Low risk: orphaned tour records can remain after course deletion or test resets

The local database currently has older tour records for missing course ids. This did not break the plugin because normal pages always load a real course first, but it is untidy and could confuse future reporting or migration scripts.

Recommended follow-up: add a course deletion observer or scheduled cleanup task to remove tours, steps, and completion records when a course is deleted.

### Medium risk: course-page targeting still depends on Moodle-rendered DOM

The main semantic target for course modules works in Moodle 5.1 using `#module-{cmid}` and related selectors. The picker also works against current 5.1 course-page markup.

This is still the plugin's biggest compatibility surface because future Moodle course format/theme changes can alter markup. The semantic model helps, but browser-side selectors remain a fallback.

Recommended follow-up: add automated browser smoke tests for:

- activity target
- section target
- block target
- course navigation/menu target
- missing target fallback

### Medium risk: course navigation/menu targets are not yet a first-class target type

Moodle 5.1 adds an `Activities` course nav item, and the existing menu structure differs slightly from the earlier screenshot pack. The current plugin can target page regions/selectors, but does not yet have a semantic target for course navigation items such as Grades, Participants, Activities, or Unit tours.

Recommended follow-up: add a semantic `course_navigation` target type for stable navigation targets.

### Medium risk: group-specific audiences are still not implemented

The plugin still supports `student`, `staff`, and `all`. Moodle 5.1 did not break this, but group audiences remain important for Murdoch workflows such as internal/external cohorts.

Recommended follow-up: add group audience tables/settings and filter playable tours using course group membership.

### Medium risk: accessibility behavior is functional but not production-grade

The popover works and uses `role="dialog"`, but it does not yet provide a full focus trap, initial focus handling, Escape key close, or robust keyboard navigation semantics.

Recommended follow-up: harden the tour player against WCAG expectations before production rollout.

### Low risk: audio relies on browser speech APIs

Audio playback remains browser-dependent. This is expected and was already documented. Moodle 5.1 does not change this, but Moodle App/webview testing is still required.

Recommended follow-up: explicitly test browser speech behavior in Moodle App webviews and define fallback messaging.

## Suggested next development pass

Completed in the production-readiness pass:

1. Added course deletion cleanup.
2. Added semantic course navigation targets.
3. Added group audience support.
4. Improved keyboard/focus accessibility in the player.

Remaining recommended dev/staging checks:

1. Add Playwright or Behat-style smoke coverage for Moodle 5.1 course pages.
2. Run full backup/restore testing with group audience tours.
3. Test Moodle App/webview playback and audio behaviour.
4. Confirm staff preview behaviour for group-specific tours matches Murdoch policy.
