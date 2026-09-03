# Release validation - 3 September 2026

## Validated environment

- Moodle 5.1.4+ (Build 20260604)
- PHP 8.2.4
- MariaDB 10.11.11
- Plugin release 0.2.0, version 2026090300
- Disposable clone of the local Moodle installation with isolated database, dataroot, PHPUnit prefix and Behat prefix

## Passed checks

- Moodle CLI upgrade recognized and installed `local_unittours` version 2026090300.
- PHP syntax check passed for all 31 plugin PHP files.
- PHPUnit passed: 3 tests, 8 assertions, no deprecations.
- Behat environment initialized successfully and the Unit Tours feature dry-run completed with no undefined steps.
- Target and event smoke test passed for section, navigation and selector resolution and for started, skipped and completed events.
- Minimal-course backup and restore passed. The restored section target was remapped to the new section ID.

## Test improvements made

- The group-audience PHPUnit fixture now enrols its student before testing group membership, matching a real Moodle course state.
- The backup/restore smoke script now fails immediately when Moodle's backup or restore CLI process exits unsuccessfully.

## Remaining hands-on acceptance

The JavaScript Behat scenarios require a Selenium/WebDriver service on port 4444. No driver is installed in this environment, so the initialized scenarios could not execute beyond startup. The in-app browser also declined access to the isolated staging URL. Before production deployment, run the existing Behat feature with a supported WebDriver and complete staff/student checks on:

- desktop Boost course pages;
- small-screen responsive web pages;
- the Moodle mobile app on Android and iOS;
- group-specific audiences using representative internal and external student groups;
- activity, section, block and course-navigation targets;
- missing-target behavior, completion, skipping, replay and urgent-news modal behavior;
- audio controls with autoplay blocked and permitted by the device/browser.

## Environment observations

- The wider cloned Moodle site emits an unrelated `mod_peerwork` subplugin metadata warning (MDL-83705).
- Restoring the existing activity-rich demonstration course fails Moodle core CLI validation for `actionuserid`. The controlled Unit Tours-only fixture restores successfully, isolating the plugin backup/restore path from that site-content issue.
