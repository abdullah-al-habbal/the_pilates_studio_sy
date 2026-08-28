# Phase 5 — Frontend

**Blocking on:** Phase 3, Phase 4.
**Context:** [A09](../findings/A09-frontend-reality.md) — less is reusable than PRD §11 assumes.

## Steps

### 5.1 API layer

`public/js/operations/api.js`

- `getBackfillSessions(purchasedAt, expiresAt, cursor, perPage)`
- `submitBackfill(payload)`

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

In the financial-details step, beside the currency selector:

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
