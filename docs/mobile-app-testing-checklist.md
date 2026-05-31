# Mobile App Testing Checklist

Use this checklist when validating `local_unittours` behavior in the Moodle mobile app.

## Core Rendering

- Course page loads without JS errors.
- `Show unit tour` launcher appears on supported pages.
- Tour popover opens and closes correctly.
- Popover content remains readable on small screens.

## Target Resolution

- Course activity targets highlight or anchor correctly.
- Section targets resolve correctly.
- Block targets resolve correctly where blocks are available.
- Navigation targets (if enabled) resolve correctly.

## Interaction & Accessibility

- Keyboard/focus flow is predictable.
- Dismiss/Skip/Next controls are reachable and readable.
- Modal/popup content does not trap or lose focus unexpectedly.
- Screen reader announces step title and body in a useful order.

## Audio

- `Play audio` appears only when audio is enabled for the step.
- Audio playback starts/stops correctly.
- Device mute/focus changes do not break tour flow.
- Auto-play fallback still allows manual play when blocked.

## Completion & State

- Completion is recorded reliably.
- Reset completion works reliably for testers/staff.
- Relaunch works after completion/reset.

## Backup/Restore

- Restored tours still open in app.
- Restored target references still resolve correctly.
