# Unit tours roadmap

## Product intent

Unit tours should let teaching staff create course-specific student guidance without knowing CSS selectors or needing site administrator access.

The plugin should preserve the strongest part of Moodle core User tours, which is contextual step-by-step guidance, while changing the ownership model from site-level administration to course-level authoring.

## Milestone 1: Course-owned tours

- Store tours, steps, and user completion state in the course context.
- Let editing teachers access a course-level management page.
- Create disabled draft tours.
- Establish semantic target types for future restore-safe mapping.
- Add basic edit pages for tours and steps.

Status: mostly complete.

## Milestone 2: Editing forms

- Add step sorting.

## Milestone 3: Student playback

- Inject enabled course tours onto course pages.
- Render a lightweight tour runner using Moodle AMD JavaScript.
- Track completion per user.
- Add a student-accessible relaunch control.

Status: first pass complete; relaunch control not started.

## Milestone 4: Semantic target resolver

- Resolve course module targets to current course module DOM nodes.
- Resolve section targets to current section DOM nodes.
- Resolve blocks and course index entries.
- Keep raw CSS selectors as a fallback only.

Status: first pass complete for playback and click-to-target authoring.

## Milestone 5: Backup and restore

- Include tours and steps in course backup.
- Remap course module target references during restore.
- Remap section target references where Moodle restore data allows.
- Mark unresolved targets for teacher review.

Status: first pass complete for tours, steps, course-module targets, and section targets.

## Milestone 6: Authoring experience

- Add click-to-target authoring on the course page.
- Convert clicks into semantic targets where possible.
- Show a target health check for each step.
- Provide unit orientation templates.

Status: basic click-to-target flow and server-side health checks started; templates not started.

## Milestone 7: Insight layer

- Track started, completed, skipped, and relaunched tours.
- Show simple staff-facing engagement summaries.
- Avoid surveillance-style analytics; keep reporting focused on tour usefulness.
