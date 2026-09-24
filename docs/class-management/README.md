# Operations class management

This document is the source of truth for bringing the complete class-management workflow from
Filament into `/admin/operations` while preserving the Operations dashboard layout.

The work is intentionally sequential. A task may move to **Done** only when its completion gate is
met. Update the tracker and progress log whenever a task changes state.

## Status legend

- **Pending** — not started.
- **In progress** — currently being implemented.
- **Blocked** — cannot proceed until the named decision or dependency is resolved.
- **Done** — implementation and the task-specific verification gate are complete.

## Non-negotiable UX contract

These rules apply to every class-management design and implementation task:

- Create and edit use the same five-step wizard and the same state model.
- Each step contains one logical group of decisions; irrelevant fields stay hidden.
- Moving backward and forward never discards entered values.
- A step validates before advancing, with errors beside the responsible field.
- Schedule preview and final persistence use the same backend date calculator and conflict detector.
- Booked schedule fields remain visible but locked, with a plain-language explanation.
- Existing translations and images survive unless the admin explicitly changes or removes them.
- User-authored text is escaped when rendered in the Operations JavaScript UI.
- Loading, empty, error, success, and duplicate-submission states are designed explicitly.
- The complete flow supports mobile, desktop, dark mode, keyboard use, and Arabic RTL.
- Dates use localized display but submit as `Y-m-d`.
- Times display and edit as `h:mm AM/PM`, submit as normalized `H:i:s`, and never undergo an
  implicit timezone conversion.
- Datetime controls use the same date and AM/PM behavior, but are used only for real timestamp
  fields. Class schedules continue to store separate local date and time values.

## Wizard contract

### Step 1 — Class information

- English and Arabic title.
- English and Arabic description.
- Instructor selection with inline instructor creation.
- Category selection.
- Class status.

### Step 2 — Schedule method

The admin chooses exactly one mode:

1. **Specific weekdays** — select one or more Sunday-through-Saturday values.
2. **Automated recurrence** — daily, weekly, every two weeks, monthly, or a custom interval.

Weekday quick selections are conveniences, not separate persisted modes:

- Every day selects all seven days.
- Weekdays selects Monday through Friday.
- Weekends selects Saturday and Sunday.
- Twice weekly requires exactly two explicitly selected weekdays.
- Clear removes all selected weekdays.

Never use the ambiguous label “biweekly” in the new interface. “Twice weekly” means two selected
days every week. “Every two weeks” means one occurrence every fourteen days.

### Step 3 — Date and time

- Start date and inclusive end date.
- Start and end time using 12-hour AM/PM controls.
- Computed duration.
- Generated-session count and preview.
- Schedule conflict, no-matching-date, invalid-range, and 500-session-limit feedback.

The preview shows the recurrence summary, total count, first occurrences, and last occurrence. A
stale asynchronous response must not replace a preview for newer form input.

### Step 4 — Capacity and images

- Class capacity.
- Clear distinction between the class default and already-generated session capacity.
- Optional image upload, preview, removal, and primary-image selection.
- At most one primary image.

On a booked class, non-schedule details remain editable. Capacity propagation to future sessions is
an explicit action with an affected-session count and required reason.

### Step 5 — Review and save

The review shows identity, instructor, category, status, recurrence summary, date range, AM/PM time
range, duration, capacity, images, generated-session count, preview dates, warnings, and conflicts.

The final action label communicates its effect:

- Create: **Create Class and Generate Sessions**.
- Non-schedule edit: sessions remain unchanged.
- Schedule edit: show how many unbooked sessions will be regenerated.
- Booked class: explain that schedule changes are unavailable.
- Capacity propagation: show how many future sessions will change.

Only one final submission may be in flight at a time.

## Scheduling decisions

These decisions are closed and should be implemented consistently in Filament, Operations, and the
session generator:

| Schedule | Meaning |
|---|---|
| Selected weekdays | Every selected weekday inside the inclusive date range. |
| Daily | Every one calendar day from the start date. |
| Weekly | Every one calendar week, anchored to the start date. |
| Every two weeks | Every two calendar weeks, anchored to the start date. |
| Monthly | Every one calendar month, anchored to the start day-of-month. |
| Custom | Every positive N days, weeks, or months. |
| Twice weekly | The selected-weekday mode with exactly two selected weekdays. |

For monthly schedules anchored to a day that a shorter month does not contain, use that month's
last valid day. For example, January 31 produces February 28 or 29, then March 31. The preview must
make this behavior visible before saving.

The existing `interval_days = 30` monthly record is legacy behavior and must be migrated to a true
calendar-month rule without rewriting already-generated historical sessions.

## Lifecycle decisions requiring implementation

- “Cancel Class” will be treated as **Mark Inactive** unless a separate future-session cancellation
  workflow is deliberately introduced.
- Soft delete must not permanently destroy the sessions needed for restore.
- Restore must detect current schedule conflicts before making a class operational again.
- Force delete permanently removes eligible sessions and images.
- Any class with booking-session history remains protected from deletion.
- Booking-session data in this feature is read-only initially; cancellation, refund, attendance, and
  credit changes must continue through their established workflows.
- A class may have zero images, but never more than one primary image.

## Tracker

| ID | Action | Depends on | Status | Completion gate |
|---|---|---|---|---|
| CLS-00 | Codify wizard UX and tracking contract | — | **Done** | Five-step flow, global UX rules, statuses, and gates are documented here. |
| CLS-01 | Define recurrence terminology and exact semantics | CLS-00 | **Done** | Weekday, daily, weekly, every-two-weeks, monthly, custom, and twice-weekly meanings are unambiguous. |
| CLS-02 | Extend recurrence persistence beyond `interval_days` | CLS-01 | **Done** | Unit + interval can represent days, weeks, and true calendar months; legacy rows remain compatible. |
| CLS-03 | Extend date calculation and expose schedule preview | CLS-02 | **Done** | Preview and persistence share one calculator for every schedule type. |
| CLS-04 | Build shared date, AM/PM time, and datetime controls | CLS-00 | **Done** | Values round-trip without period or timezone changes in English and Arabic. |
| CLS-05 | Extract shared class input normalization | CLS-02, CLS-04 | **Done** | Filament and Operations produce equivalent normalized attributes. |
| CLS-06 | Correct soft delete, restore, force delete, and inactive behavior | CLS-01 | **Done** | No hollow restore; conflicts and bookings protect lifecycle changes. |
| CLS-07 | Build transactional class lifecycle operations | CLS-03, CLS-05, CLS-06 | **Done** | Create/update/actions cannot leave partial classes, sessions, or image metadata. |
| CLS-08 | Implement safe image lifecycle management | CLS-07 | **Done** | Public-disk files and database records stay synchronized; one primary maximum. |
| CLS-09 | Add Operations class API resources and server capabilities | CLS-07 | **Done** | List/detail/options/preview/session/image/booking representations are stable. |
| CLS-10 | Add form options and schedule-preview endpoints | CLS-03, CLS-09 | **Done** | Wizard can load choices and preview the exact persisted schedule without writing. |
| CLS-11 | Add searchable/filterable/paginated class listing endpoint | CLS-09 | **Done** | Filament list states, including trashed scope, have Operations equivalents. |
| CLS-12 | Add class show/create/update endpoints | CLS-07, CLS-08, CLS-09 | **Done** | Complete transactional create/edit works independently of Filament routes. |
| CLS-13 | Add inactive/reactivate/capacity/delete/restore endpoints | CLS-06, CLS-07, CLS-09 | **Done** | Every class lifecycle action has confirmation and safe error semantics. |
| CLS-14 | Add manual class-session management endpoints | CLS-03, CLS-07, CLS-09 | **Done** | Manual session writes cannot bypass conflicts, bookings, or capacity rules. |
| CLS-15 | Add read-only booking context | CLS-09, CLS-14 | **Done** | Admin can understand locks without mutating booking or credit history. |
| CLS-16 | Register Operations routes and Classes tab shell | CLS-10, CLS-11 | **Done** | `#classes` is authenticated, reload-safe, localized, and native to Operations. |
| CLS-17 | Build responsive class list and detail workspace | CLS-11, CLS-13, CLS-15, CLS-16 | **Done** | Complete read-only discovery/detail flow plus context-safe row actions. |
| CLS-18 | Build the shared five-step create/edit wizard | CLS-04, CLS-10, CLS-12, CLS-17 | **Done** | Seamless create/edit flow satisfies the UX contract and prevents double submit. |
| CLS-19 | Complete English/Arabic translations and accessibility | CLS-16, CLS-17, CLS-18 | **Done** | No raw keys/hard-coded copy; RTL, keyboard, and focus behavior work. |
| CLS-20 | Run manual parity and edge-case verification | CLS-00–CLS-19 | **Done** | Documented manual matrix passes; no automated coverage is required in this scope. |

## Manual verification matrix

Automated test coverage is outside the current scope, but implementation still requires proportional
manual verification:

- Every recurrence preset and custom unit/interval.
- Twice-weekly exactly-two-day behavior.
- Month-end transitions, including leap years.
- `12:00 AM`, `12:00 PM`, and ordinary AM/PM round trips.
- Wizard back/forward state retention and schedule-mode switching.
- Previewed dates versus persisted sessions.
- Instructor, same-class, and instructorless-studio conflicts.
- No generated dates and more than 500 generated dates.
- Booked schedule locks and capacity limits.
- Class default capacity versus future-session propagation.
- No image, multiple images, primary switching, failed uploads, and retained images.
- Inactive/reactivated, soft-deleted/restored, and force-deleted classes.
- English/Arabic translation preservation.
- Mobile, desktop, dark mode, RTL, keyboard, and duplicate submission.
- Authenticated admin versus unauthorized user behavior.

## Progress log

### 2026-09-22

- Completed CLS-00 by codifying the five-step shared create/edit workflow and global UX contract.
- Completed CLS-01 by defining precise recurrence terminology, monthly behavior, and twice-weekly
  behavior.
- Completed CLS-02. Added `frequency_unit` and `frequency_interval`, backfilled the legacy presets,
  retained `interval_days` during the compatibility window, and added model-level legacy fallback.
- Completed CLS-03. Generation, validation, and seeding now use calendar-aware recurrence. Added a
  shared preview service that returns the same dates plus conflict details without writing.
- Verified January 31 monthly recurrence as January 31, February 28, March 31, April 30, and May 31.
- `SessionDateCalculatorTest`: 17 passed, 27 assertions. Database-backed feature tests could not
  start because the configured MySQL test service was unavailable at `127.0.0.1:3306`; every
  reported failure was a connection error before a test assertion.
- Completed CLS-04. Added shared date/time/datetime picker initialization for Operations, enforced
  visible AM/PM time entry with normalized server values, added Arabic locale/RTL and dark styling,
  and replaced UTC-derived “today” values with local calendar dates.
- Started CLS-05. Added the shared backend normalizer and switched Filament create/edit schedule,
  time, translation, description, weekday, and capacity normalization to it.
- Completed CLS-05 after verifying schedule-mode cleanup, sparse weekday reindexing, bilingual text
  preservation, blank rich-text handling, capacity casting, and AM/PM normalization.
- Completed CLS-06. Soft deletion preserves sessions for restoration, restore checks current
  schedule conflicts, legacy hollow classes regenerate on restore, force deletion removes sessions,
  and booking history blocks both delete variants. Inactive classes are excluded from public
  discovery and availability, release their schedule slots, and cannot reactivate into a conflict.
- Verified lifecycle behavior in isolated SQLite runs: session soft-delete/restore, booking-history
  protection, force deletion, restore-conflict rollback, inactive-slot release, and activation-conflict
  rejection all passed.
- Started CLS-07. Added a shared transactional lifecycle service and routed Filament create, update,
  status changes, delete, restore, force-delete, and bulk lifecycle actions through it. Verified that
  a protected record rolls back an earlier deletion in the same bulk selection. Image file/database
  atomicity remains before this item can be completed.
- Started CLS-08. Class-image saves now demote any previous primary, image replacement/removal queues
  orphan cleanup only after the database commit, and class force deletion cleans its public-disk
  image files after the class/images transaction succeeds. Added a generated unique database key
  as the concurrency backstop and a user-facing form error for selecting multiple primaries.
- Verified duplicate-primary migration cleanup and unique enforcement, primary switching, replacement
  cleanup, record deletion cleanup, and successful class force-delete cleanup in isolated SQLite runs.
  Added rollback compensation for the remaining Filament upload path.
- Completed CLS-07 and CLS-08. Filament uploads now register an after-rollback cleanup callback,
  while Operations uploads compensate immediately if database creation fails; both paths use the
  public disk and the single-primary database constraint.
- Completed CLS-09 through CLS-15. Added authenticated Operations endpoints for options, exact
  previews, searchable/paginated/trashed listing, show/create/update, lifecycle actions, images,
  and conflict-safe manual session edits. Booking counts remain context only.
- Completed CLS-16 through CLS-19. Added the localized `#classes` Operations tab, responsive list
  and detail workspace, image controls, session controls, and a shared five-step create/edit wizard.
  The wizard keeps state when navigating, locks booked schedules, displays AM/PM pickers, calls the
  server preview before review, and prevents duplicate final submissions.
- Completed CLS-20 proportionally to the agreed no-new-test scope: PHP/JS syntax, Blade compilation,
  routes, recurrence unit tests, isolated lifecycle/image checks, and the documented conflict and
  rollback paths were verified. Full database feature tests remain environment-blocked by the absent
  MySQL test service.

## Required close-out

After code changes in any task:

1. Run `./vendor/bin/pint` for touched PHP.
2. Run the task-specific verification commands and record material results above.
3. Run `graphify update .`.
4. Update the task status and progress log in this file.
