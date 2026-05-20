# Scheduler UI Specification

**Entry:** `GET /admin/scheduler`  
**State:** `window.Scheduler` (`public/js/scheduler/state.js`)

## Script load order

`state.js` → `ui.js` → `toaster.js` → `api.js` → `templates.js` → `render.js` → `modal.js` → `walkin.js` → `events.js` → `main.js`

## Main view

- Date input + Today + Refresh
- Session cards rendered client-side (`templates.js` / `render.js`)
- Pagination for daily list

## Session modal

- **Attendees tab:** toggle attended/missed via `postAttendance(sessionId, bookingSessionId, status)`
- **Walk-in tab:** existing member multi-select or new member form with live field validation

## Acceptance

- **Given** a session with capacity, **when** adding walk-in, **then** attendee list refreshes and credits decrement per business rules.
- **Given** network error, **then** toast shows error message.
