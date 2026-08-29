# Phase 5 — Frontend

**Blocking on:** Phase 3, Phase 4.
**Context:** [A09](../findings/A09-frontend-reality.md) — less is reusable than PRD §11 assumes.

## Three steps, not five

PRD §11.2 sketches a five-step indicator beginning with customer selection. **Decided
2026-08-28: the stepper has three steps and no customer step.**

The flow is only ever reached from the client details modal
(`public/js/operations/modules/clients.js`), so the client is already chosen. There is no
general entry point without a preselected client, and a step whose answer is already known is
friction, not guidance.

| Step | Content |
|---|---|
| 1 | Package, historical purchase date, currency, optional exchange-rate override |
| 2 | Session selection, fed by `GET /bookings/backfill/sessions` with `user_id` |
| 3 | Review and submit |

The PRD's separate "Select Attended" and "Select Missed" steps are collapsed into step 2: one
list where each row is toggled to attended, missed, or unselected. That removes the need to
re-page the same window twice, and makes the mutual-exclusion rule structural rather than a
cross-step validation.

## Steps

### 5.1 API layer

`public/js/operations/api.js`

- `getBackfillSessions(purchasedAt, expiresAt, cursor, perPage)`
- ~~`submitBackfill(payload)`~~ — removed; `assignPackage()` takes an optional backfill payload as its fourth argument instead

Both through the existing `request()` wrapper (`:1-37`) — CSRF, 10 s `AbortController`, shared
error shape.

### 5.2 New module

`public/js/operations/modules/backfill.js`

| Piece | Approach |
|---|---|
| Stepper | **Net-new** — nothing to copy ([A09](../findings/A09-frontend-reality.md)) |
| Selection state | Two `Set`s held outside the DOM, so selections survive paging (PRD §10) |
| Session list | Infinite scroll copied from `store.js:306-361` |
| Live arithmetic | `attended + missed + remaining = total`, recomputed on input |
| Submit gate | Disabled until both selection counts match their declared counts exactly |
| Mutual exclusion | A session chosen as attended is disabled in the missed pool |

### 5.3 Exchange-rate field — [D-A03](../decisions/D-A03-exchange-rate-override.md)

In **step 1**, beside the currency selector — the rate belongs with the money, and putting it on
the review step would invite editing a figure the admin has already mentally signed off:

| Property | Value |
|---|---|
| Label | "Exchange Rate" |
| Default | current rate for the selected currency |
| Hint | *"Leave as-is for current rate, or enter the historical rate if known."* |
| Emphasis | De-emphasised — the admin should feel free to ignore it |
| On currency change | Re-fill the default, **unless** the admin has already edited the field |

Rates are stored as `decimal(12,6)`; more than six decimal places are silently rounded by
MySQL. Say so in the hint.

### 5.4 Wire-up

- `public/js/operations/main.js` — import the module
- `public/js/operations/modules/clients.js:230-390` — add the trigger button beside Assign / Freeze / Refund

### 5.5 Fix `window.__()` while adding keys

`resources/views/layouts/operations.blade.php:171-174` re-inlines the whole `@json` payload on
**every** call. Hoist the literal out of the closure before adding ~40 backfill keys, otherwise
every existing `__()` call pays for them.

### 5.6 Render the D-A01 rejection message

The message is multi-line with bullets; `OperationsUI.toast()` renders into HTML where `\n`
collapses. Pick one:

- `white-space: pre-line` on the toast body, or
- split the key into a `title` plus an `options[]` array rendered as a list

## Done when

- Selections persist across paging.
- Submit stays disabled until counts match.
- A D-A01 rejection is legible, with all three placeholders filled and the bullets intact.
