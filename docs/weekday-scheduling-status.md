# Weekday Scheduling — Status & Actionable Checklist

> **Purpose:** living checkpoint for the weekday-based class scheduling feature.
> Update the checkboxes and the Changelog at the bottom as work lands.
> Companion audit (evidence, file citations, full matrix): `/home/lenovo/.claude/plans/tender-moseying-rabbit.md`

| | |
|---|---|
| **Last updated** | 2026-08-23 |
| **Phase** | Phases 1–3 done. Remaining: browser pass + production migration |
| **Tests** | **91 passing / 0 failing** (198 assertions, ~8s) |
| **Branches** | `feature/strict-scheduling-rules` → `275be5f`, `efca3e5`<br>`fix/booking-overbooking` → `9e6bfae` (current) |
| **Migration status** | applied to **dev** and `pilates_studio_test_db`. **Not** production |

---

## 1. What this feature does

A class is scheduled by **exactly one** of two modes:

| Mode | Column | Behaviour |
|---|---|---|
| **Weekday** (new) | `classes.weekdays` json, e.g. `["sunday","wednesday"]` | Sessions generated only on the selected weekdays inside `[start_date, end_date]` |
| **Interval** (existing) | `classes.recurrence_pattern_id` (now nullable) | Fixed `interval_days` stride from `start_date` — unchanged behaviour |

Conflicts are checked on real datetime windows using the half-open predicate
`existing_start < new_end AND existing_end > new_start`, so back-to-back sessions
(16:00–17:00 then 17:00–18:00) do **not** collide.

### Reference scenario (locked as a test fixture)

`2026-08-01 → 2026-10-01`, `16:00–17:00`, Sunday + Wednesday → **18 sessions**:

```
2026-08-02 Sun   2026-08-19 Wed   2026-09-06 Sun   2026-09-23 Wed
2026-08-05 Wed   2026-08-23 Sun   2026-09-09 Wed   2026-09-27 Sun
2026-08-09 Sun   2026-08-26 Wed   2026-09-13 Sun   2026-09-30 Wed
2026-08-12 Wed   2026-08-30 Sun   2026-09-16 Wed
2026-08-16 Sun   2026-09-02 Wed   2026-09-20 Sun
```

`2026-08-01` is a **Saturday** → excluded. `2026-10-01` is a **Thursday** → excluded.
Old behaviour (`interval_days = 7`) produced 9 **Saturdays** — 0/18 correct.

---

## 2. Locked decisions (owner, 2026-08-22)

| Decision | Answer |
|---|---|
| Coexistence | **Two modes.** Exactly one of `weekdays` / `recurrence_pattern_id` must be set |
| Conflict scope | **Instructor overlap** + **same-class self-overlap**. Cross-class overlap between *different, non-null* instructors is allowed |
| Instructor-less classes | **Block any overlap, both directions** (decided 2026-08-23). A class with no instructor is treated as occupying the studio |
| Cancelled sessions | **Release their slot** (do not block) |
| Partial conflicts | **Reject the whole batch** — nothing is written |
| Midnight crossing | **Forbidden** — `end_time > start_time` enforced at the service layer |

---

## 3. DONE

### 3.1 New files

- [x] `app/Enums/WeekdayEnum.php` — string-backed (`sunday`…`saturday`), `HasLabel`, `options()`, `values()`, `carbonDayOfWeek()`, `fromCarbonDayOfWeek()`, `normalise()` (dedupes + canonical Sunday-first order)
- [x] `app/Services/Classes/SessionDateCalculator.php` — pure date math, no DB/Eloquent/clock. `forWeekdays()`, `forInterval()`, `MAX_SESSIONS = 500` guard
- [x] `app/Services/Validation/SessionConflictDetector.php` — `detect()`, `assertNoConflicts()`, `assertNoDuplicates()`
- [x] `app/ValueObjects/Scheduling/ScheduleConflictVO.php` — conflict shape + `describe()` for the error message
- [x] `database/migrations/2026_08_22_132552_add_weekdays_to_classes_table.php` — adds `weekdays` json nullable; makes `recurrence_pattern_id` nullable (FK preserved)

### 3.2 Modified files

- [x] `app/Models/Classes.php` — `weekdays` fillable + `'array'` cast; `hasWeekdaySchedule()`, `hasIntervalSchedule()`, `weekdayCases()`
- [x] `app/Services/Validation/ClassScheduleValidationService.php` — rewritten: `assertExactlyOneMode()`, `assertValidTimes()`, `assertEndDatePresent()`, mode-aware `assertValidWindow()` (nullable interval; `start == end` now legal)
- [x] `app/Services/Classes/ClassSessionGenerationService.php` — `generate()` wrapped in `DB::transaction`; strategy branch via `datesFor()`; duplicate + conflict passes before insert; all booking guards preserved verbatim
- [x] `app/Observers/ClassesObserver.php` — `'weekdays'` added to `SCHEDULE_FIELDS`; `created()` always generates (was silently skipping when no pattern was set)
- [x] `app/Filament/.../Schemas/ClassesForm.php` — `schedule_mode` ToggleButtons (form-only, `dehydrated(false)`), `weekdays` CheckboxList, recurrence Select moved into the Schedule section and no longer unconditionally required; `normaliseScheduleMode()` helper; window rule is mode-aware and now also covers times
- [x] `app/Filament/.../Pages/CreateClasses.php` / `EditClasses.php` — `mutateFormDataBeforeCreate/Save` → `normaliseScheduleMode()`; `$hasDatabaseTransactions = true` scoped to these two pages
- [x] `app/Filament/.../Schemas/ClassesInfolist.php` — weekday badges shown in weekday mode, recurrence entry in interval mode
- [x] `app/Filament/.../Tables/ClassesTable.php` — `schedule_summary` badge column (toggleable)
- [x] `app/Filament/.../RelationManagers/SessionsRelationManager.php` — `conflictRule()` on `date` / `start_time` / `end_time`. **Closes the unvalidated back door** into `class_sessions`
- [x] `app/Http/Resources/Api/V1/ClassesResource.php` — exposes `weekdays` (readable names, `null` in interval mode)
- [x] `database/factories/ClassesFactory.php` — weekday mode is now the default; `interval()` and `onWeekdays()` states; `end_date` is never null; range widened to clear the 30-day pattern
- [x] `database/seeders/ClassesSeeder.php` — "Mat Essentials" converted to Sunday + Wednesday; other two stay on interval; random classes split 7 weekday / 3 interval / 3 inactive
- [x] `database/seeders/ClassSessionSeeder.php` — now delegates to `SessionDateCalculator`, removing the divergent second implementation
- [x] `resources/lang/en/dashboard.php` + `resources/lang/ar/dashboard.php` — `weekdays.*` day names, `classes.validation.*` messages, weekday field/helper/placeholder labels. **Also backfills the previously missing Arabic recurrence keys**

### 3.3 Bugs fixed as a side effect

- [x] **R4 — non-atomic create.** `generate()` is now transactional *and* the Classes pages wrap the whole action, so a rejected schedule no longer leaves a committed class with zero sessions. Proven by `ClassesAdminPanelTest::the_form_surfaces_an_instructor_conflict_instead_of_creating_the_class` (which failed with `2 !== 1` before the fix)
- [x] **R6 — `end_date = null` silently truncating the range to "today"** → now an explicit validation error
- [x] **R8 — `SessionsRelationManager` back door** → now runs the same conflict rules
- [x] **R10 — divergent seeder generation logic** → unified on `SessionDateCalculator`
- [x] **R12 — missing Arabic keys** → added
- [x] **Midnight-crossing / zero-length sessions** — previously only the Filament UI blocked `end_time <= start_time`; factories, seeders and tinker could create them. Now enforced in the service
- [x] **Silent no-op** — a class with no recurrence pattern used to be created with zero sessions and no error

### 3.4 Test infrastructure (built from nothing — `tests/` was empty)

- [x] `tests/TestCase.php` created
- [x] **Isolated MySQL test database `pilates_studio_test_db`** created. Dev DB untouched
- [x] `PackageFactory` — SYP/USD currencies switched from `Currency::factory()->create()` to
      `firstOrCreate`. Creating a second package in one test used to violate
      `currencies_code_unique`, which made the booking subsystem effectively untestable
- [x] `phpunit.xml` — `DB_CONNECTION` sqlite → mysql, `DB_DATABASE` `:memory:` → `pilates_studio_test_db`
      *Reason: the suite could never have run on sqlite — `users.is_active` uses `storedAs("IF(...)")`, which SQLite cannot parse. The sqlite config was aspirational.*
- [x] `.env.testing` — **was pointing tests at the dev database on the wrong port (3307)**. Now `APP_ENV=testing`, port `3306`, `DB_DATABASE=pilates_studio_test_db`. This was a live footgun: any `RefreshDatabase` run would have wiped dev data. Backup: `<scratchpad>/.env.testing.bak`

### 3.5 Test coverage — 75 passing

| Count | File | Covers |
|---:|---|---|
| 17 | `tests/Unit/SessionDateCalculatorTest.php` | The 18-date fixture; Saturday start excluded; inclusive end; 1/7 weekdays; duplicates; ordering; 500 cap; `interval_days` 1/7/14/30 all proven unable to reproduce the weekday set |
| 26 | `tests/Feature/SessionConflictDetectorTest.php` | All 5 overlap shapes + both back-to-back directions; instructor scope; different-instructor allowed; self-overlap; **studio contention in both directions**; cancelled releases slot; soft-deleted ignored; completed blocks; soft-deleted class ignored; `ignoreSessionId`; batch rejection; unique-index pre-check |
| 15 | `tests/Feature/ClassWeekdayGenerationTest.php` | Generation via the real observer; times/capacity copied; **no bookings ever created**; interval mode intact; both/neither mode rejected; time and date-range rules; single-day range; one conflict rejects all 18 rows |
| 10 | `tests/Feature/ClassRegenerationTest.php` | Weekday/date/time changes regenerate; mode switching; idempotence; non-schedule change does *not* regenerate; booked-class protection (update + delete); unbooked delete cascades |
| 6 | `tests/Feature/ClassesAdminPanelTest.php` | Real Filament pages: list renders; admin creates a weekday class → 18 sessions; empty weekdays rejected; bad times rejected; edit to interval mode clears weekdays; instructor conflict surfaces **and creates nothing** |
| 5 | `tests/Feature/ClassesFormSchemaTest.php` | Form schema builds; mode normalisation clears the unused column in both directions |
| 12 | `tests/Feature/BookingCapacityTest.php` | Cancelled reservations release their spot; repository and model accessor agree; zero-capacity is unbookable; full session rejected; capacity never exceeded; **query-order guards** for lock-before-count and lock ordering. **7 of the 12 fail if either fix is reverted** |

### 3.6 Verified end-to-end

- [x] `migrate:fresh --seed` on the test DB: 16 classes (11 weekday / 5 interval), 569 sessions, **0 classes with both modes set**
- [x] "Mat Essentials" seeds `["sunday","wednesday"]` and generates Sun/Wed dates only
- [x] Migration verified on MySQL: `recurrence_pattern_id` `IS_NULLABLE=YES`, FK still present, `weekdays` added
- [x] `./vendor/bin/pint` clean

---

## 4. NOT DONE — actionable

### 4.1 Blocking rollout

- [x] ~~Review the working tree / commit on a branch~~ **DONE.** 33 files on
      `feature/strict-scheduling-rules` (`275be5f`, `efca3e5`), then `fix/booking-overbooking`
      (`9e6bfae`). The two pint-only diffs in `routes/web/operations/finance.php` and
      `routes/web/scheduler/walkin.php` (blank line after `<?php`) went in with the first
      commit — unrelated to this feature, drop them if you prefer a clean diff.
- [x] ~~Run the migration on dev~~ **DONE.** `recurrence_pattern_id` is nullable, FK intact,
      `weekdays` added. **Note: the dev database had 0 classes and 0 sessions**, so nothing
      was migrated in anger — `InstructorSeeder` was run (additive, `firstOrCreate`) to give
      the browser pass something to work with. Dev now has 3 instructors, 3 categories,
      4 recurrence patterns, 2 admins.
- [ ] **Run the migration on production.** Still outstanding. Additive and non-destructive,
      but production has real class data, unlike dev.
      *Acceptance:* existing classes still list their sessions; no class has both modes set:
      `select count(*) from classes where weekdays is not null and recurrence_pattern_id is not null;`
- [ ] **Decide `.env.testing` / `phpunit.xml`.** Both are git-tracked and now point at
      `pilates_studio_test_db`. Confirm this is what you want committed, and that CI (if any)
      creates that database. `.github/workflows/deploy.yml` does not currently run tests —
      check whether it should.
- [ ] **Manual browser pass** on `/admin/classes` — automated tests cover the Livewire layer
      but nothing has rendered in a real browser:
      - [ ] Mode toggle switches which field is shown
      - [ ] Weekday checkboxes render in both `en` and `ar` (RTL)
      - [ ] Conflict error message is readable and names the colliding class
      - [ ] A class with bookings shows every schedule field disabled
      - [ ] `schedule_summary` column renders in the table
      - [ ] Infolist shows weekday badges / recurrence label per mode

### 4.2 Known gaps in this feature

- [x] ~~**Instructor-less classes get no conflict check at all.**~~ **RESOLVED 2026-08-23**
      (`efca3e5`). A class with no instructor now conflicts with every overlapping class, in
      **both** directions — an unstaffed candidate contends with all existing sessions, and an
      existing unstaffed session blocks a candidate that does have an instructor. Without the
      second direction, creation order would decide whether the collision was caught. New
      `ScheduleConflictVO::REASON_STUDIO` distinguishes it in the error message.
      No rooms table was added; this is the proxy for single-studio contention.
- [ ] **`ClassSessionResource` (API) exposes no weekday context.** `ClassesResource` does.
      Add only if the mobile app needs it.
- [ ] **No API endpoint to create/update classes.** There never was one; the audit
      recommended keeping it out of scope. If it is ever added it needs
      `StoreClassRequest` with
      `weekdays => ['array','min:1','max:7']`, `weekdays.* => [Rule::enum(WeekdayEnum::class),'distinct']`,
      and it must route through `ClassSessionGenerationService` — not write sessions directly.
- [ ] **`regenerate()` assigns new session ids every time** (R13). Anything holding a
      `class_session_id` across a schedule edit loses its anchor — e.g. `reminder_sent_at`,
      queued push notifications. Not triggered by this change; still true.
- [ ] **Cancelled sessions release their slot for conflicts but still occupy the DB unique
      index** `(class_id, date, start_time)`. `assertNoDuplicates()` pre-checks this so it
      surfaces as a readable message, but the two rules genuinely disagree by design.
      Documented in `SessionConflictDetector`.

### 4.3 Pre-existing bugs found in the audit — still open, deliberately untouched

Each is real, reproducible, and **unrelated to weekday scheduling**. Verified still present.

- [x] ~~**R2 — capacity mis-counted two different ways.**~~ **FIXED 2026-08-23** (`9e6bfae`).
      `getAvailableSpots()` now filters to `reserved` and returns `0` (not `PHP_INT_MAX`) for
      non-positive capacity. It agrees with `ClassSession::getAvailableSpotsAttribute()`, and
      a test asserts they stay in agreement. `countUpcomingFullSessions()` had the same
      missing filter and was fixed too.
- [x] ~~**R3 — TOCTOU race in reserve.**~~ **FIXED 2026-08-23** (`9e6bfae`). The class session
      is locked before its reservations are counted. Also: `reserve()` locked booking→session
      while `oneTimeAttend()` locks session→booking, so the two could deadlock against each
      other; both now lock session→booking. Guarded by a test asserting query order, since a
      single-process test cannot reproduce the race itself.
- [ ] **R9 — orphaned duplicate handlers.** *(now the highest-value remaining cleanup: it is
      an unguarded copy of the code just fixed in R2/R3)* `app/Handlers/BookingSession/ReserveSessionHandler.php`
      (no transaction, no lock, **no capacity check whatsoever**) and `CancelSessionHandler.php`
      are referenced nowhere. Wiring either up reintroduces overbooking.
      *Fix:* delete both.
- [ ] **R7 — soft-deleting a class hard-deletes its sessions.** `ClassesObserver::deleting`
      runs `$class->sessions()->forceDelete()` (base-query delete, scopes not applied) while
      `Classes` uses `SoftDeletes` → restoring a class yields zero sessions, unrecoverable.
      Covered by a test asserting *current* behaviour, so changing it will fail that test on purpose.
- [ ] **`SendSessionRemindersCommand` compares date and time separately**
      (`whereDate('date', …)` + `whereBetween('start_time', …)`), so it silently misses any
      window straddling midnight. Harmless while midnight crossing is forbidden — becomes a
      bug the moment that rule is relaxed.
- [ ] **R11 — duplicate indexes.** `class_sessions` has both `idx_class_id` and
      `idx_sessions_class` on plain `class_id`; `classes` has overlapping `idx_classes_*`.
      Wasteful, harmless. Left alone to keep this migration focused.
- [ ] **Class create form always requires a gallery image.** The `images` Repeater seeds one
      empty row whose upload is `required` on create, so a class cannot be created without an
      image. Pre-existing and possibly intentional — confirm.

### 4.4 Optional follow-ups

- [ ] Enable `->databaseTransactions()` panel-wide in `AdminPanelProvider` instead of
      per-page. Deliberately **not** done: it would wrap every Filament write in the app
      (merchandise, bookings, refunds), none of which have tests.
- [ ] `AdminPanelProvider` never calls `->default()`. Harmless in HTTP (the panel resolves
      from the URL) but Livewire tests must call `Filament::setCurrentPanel('admin')`.
      Adding `->default()` would remove that test-side workaround.
- [ ] Backfill tests for the untested subsystems this work exposed — bookings, refunds,
      walk-ins, merchandise all have zero coverage.

---

## 5. Architecture after the change

```
Filament ClassesForm  (schedule_mode → normaliseScheduleMode → exactly one column set)
  └─> Classes  { weekdays json | recurrence_pattern_id }      ← page-scoped DB transaction
        └─> ClassesObserver::created / updated                  (SCHEDULE_FIELDS incl. weekdays)
              └─> ClassSessionGenerationService                 ← DB::transaction
                    ├─ ClassScheduleValidationService           mode, times, window
                    ├─ SessionDateCalculator                    pure → list<Carbon>
                    ├─ SessionConflictDetector                  half-open overlap, reject batch
                    └─ ClassSession::insert()                   ← fires no model events
                          └─> ClassSession
                                └─> BookingSession  ← only ever created by booking flows
```

`SessionsRelationManager` (manual add/edit) now enters at `SessionConflictDetector` too.

---

## 6. How to verify

```bash
# Full suite — expect 91 passed
php artisan test

# Just this feature
php artisan test tests/Unit/SessionDateCalculatorTest.php \
                 tests/Feature/SessionConflictDetectorTest.php \
                 tests/Feature/ClassWeekdayGenerationTest.php \
                 tests/Feature/ClassRegenerationTest.php \
                 tests/Feature/ClassesAdminPanelTest.php \
                 tests/Feature/ClassesFormSchemaTest.php \
                 tests/Feature/BookingCapacityTest.php

# Seed the ISOLATED test database (never the dev one — check the name first)
php artisan tinker --env=testing --execute='echo DB::getDatabaseName();'
php artisan migrate:fresh --seed --force --env=testing

# Style
./vendor/bin/pint
```

Manual: `/admin/classes` → create a class, mode **Repeat on**, tick Sunday + Wednesday,
`2026-08-01` → `2026-10-01`, `16:00`–`17:00` → expect 18 sessions starting `2026-08-02`.
Then create a second class, same instructor, `16:30`–`17:30`, overlapping dates → expect
rejection naming the conflicting date, and **no** new class row.

---

## 7. Changelog

| Date | Change |
|---|---|
| 2026-08-22 | Audit completed; owner locked the four design decisions |
| 2026-08-23 | Feature implemented: enum, migration, calculator, detector, service/validator/observer rewrite, Filament UI, seeders/factory, both locales |
| 2026-08-23 | Test infrastructure built from scratch; isolated test DB created; `.env.testing` dev-DB footgun fixed |
| 2026-08-23 | R4 atomicity gap closed via page-scoped `$hasDatabaseTransactions` after a test caught a conflicting class committing with zero sessions |
| 2026-08-23 | 75/75 tests green, pint clean, seeded data verified |
| 2026-08-23 | **Phase 1:** committed to `feature/strict-scheduling-rules`; dev migrated (found dev had 0 classes); `InstructorSeeder` run so the browser pass has data |
| 2026-08-23 | **Phase 2:** instructor-less classes now block any overlap, both directions (`efca3e5`) |
| 2026-08-23 | **Phase 3:** R2 capacity leak + R3 reserve race fixed, plus a latent deadlock from inconsistent lock ordering; `PackageFactory` made reusable (`9e6bfae`). 91/91 green |
| | _next: browser pass on `/admin/classes`, then the production migration_ |
