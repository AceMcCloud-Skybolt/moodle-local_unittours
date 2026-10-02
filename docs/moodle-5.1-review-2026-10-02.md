# Unit Tours: Moodle 5.1 compatibility review

## Scope and environment

Reviewed on 2 October 2026 against Moodle 5.1.4+ (Build: 20260604), PHP 8.2.4, MariaDB 10.11.11 and PHPUnit 11.5.55 in an isolated Windows test installation. No university-site data was accessed or changed.

This is compatibility evidence, not production certification. The institution's target Moodle patch release, theme, role configuration, integrations and upgrade path still require acceptance testing. See Moodle's [5.1 developer update](https://moodledev.io/docs/5.1/devupdate) for the upstream API changes.

## Automated evidence

- PHP syntax, Moodle coding standard (zero warnings), PHPDoc, plugin structure and upgrade savepoint checks pass.
- Literal language-string references were checked against the installed Moodle 5.1 string manager; no missing references remain. Dynamic identifiers need workflow testing too.
- The saved final PHPUnit report confirms the Unit Tours suite passed: 4 tests, 10 assertions, zero failures, errors or skipped tests, including the management-screen renderer test.
- Template lint on Windows encountered an upstream mixed-path-separator limitation; Linux GitHub Actions runs the installed-plugin template checks.
- GitHub Actions run 36947755256 confirmed installation, PHP lint, structure, savepoints, coding standard, PHPDoc and PHPUnit passed on Linux against Moodle 5.1.7+ on both PHP 8.2 and 8.3. Template lint flagged missing example contexts in both staff templates; example data has been added and requires a follow-up CI run.

## Changes

- Add a management-screen rendering test through Moodle's renderer and Mustache APIs.
- Add GitHub Actions for Moodle 5.1 on PHP 8.2 and 8.3.
- Moodle's AMD ESLint checks pass for the player source.

No runtime or database schema change; the existing release/version is retained.

## Deployment and acceptance

The local demo installation was older than this repository. Deploy the complete current plugin and its compiled AMD files, run Moodle upgrade and purge caches after taking backups.

Validate tour creation/editing, target resolution after the Moodle upgrade, student playback, restricted/hidden content, completion, audio, keyboard/focus behavior and responsive layout under the institutional theme. Moodle app/webview playback remains separate acceptance work.

Use the [Moodle 5.1 UAT smoke checklist](moodle-5.1-uat-smoke-checklist.md) to record the upgrade environment results. GitHub Actions must complete successfully after this workflow is published; adding the workflow is not evidence of a completed Linux or PHP 8.3 run.
