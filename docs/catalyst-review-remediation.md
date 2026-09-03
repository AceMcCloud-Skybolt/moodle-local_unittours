# Catalyst IT Review Remediation

This ledger maps the July 2026 Catalyst IT findings for `local_unittours` to release 0.2.0.

## Catalyst direct fixes ported

| ID | Resolution |
|---|---|
| 1.1-1.2 | Added class and method PHPDoc throughout the repository, privacy, hook, backup/restore, and library code. |
| 1.3 | Declared Moodle 4.5 through 5.1 support and alpha maturity. |
| 1.4 / 2.1 | Converted delete and reorder mutations to confirmed, sesskey-protected POST requests. |
| 2.2 | Documented the accepted sesskey exposure in the AMD initialisation payload. |
| 3.1-3.3 | Batched step counts, steps, completion ids, and group-audience lookups to remove N+1 queries. |
| 3.4 | Made draft seed content language-independent so backup/restore is deterministic. |
| 3.5 | Made course-page detection compatible with custom course formats. |

Catalyst's Moodle 5.1 hook migration (`db/hooks.php` and the before-footer hook callback) was also ported with these changes.

## Additional recommendations addressed

| ID | Resolution |
|---|---|
| 1.5 | Confirmed `lib.php` has the `MOODLE_INTERNAL` guard. |
| 2.3 | Decode stored HTML entities through a temporary textarea before passing text to browser speech synthesis. |
| 4.1 | Added theme-overridable Mustache templates for the tour list and tour detail views. |
| 4.2 | Added `local_unittours\output\renderer` and moved management presentation logic into it. |
| 4.3 | Converted both AMD source modules to named ES module exports with `const`/`let`; rebuilt production files with Moodle's pinned Babel and Terser packages. |
| 4.4 | Added repository PHPUnit coverage for reorder boundaries, completion idempotency/status validation, and group-audience filtering. |

## Verification

- PHP syntax lint: passed for every PHP file.
- Moodle PHP CodeSniffer: passed with zero errors and zero warnings.
- Moodle ESLint: passed with zero errors and zero warnings.
- AMD build: regenerated successfully using the dependencies installed with Moodle 5.1.
- PHPUnit and database smoke tests: not executed in the Codex sandbox because the configured Moodle test dataroot is not writable by the sandbox account. Run the commands in the README under the normal development account.
