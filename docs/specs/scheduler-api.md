# Scheduler API Specification

Base path: `/admin/scheduler`  
Middleware: `web`, `auth`, `freeze.user`,

## Endpoints

### `GET /` — Scheduler page (HTML)

### `GET /sessions`

| Query | Type |
|-------|------|
| `date` | YYYY-MM-DD, required |
| `page` | int |
| `per_page` | int |

**Data:** paginated scheduled sessions for date.

### `GET /sessions/{sessionId}`

Session detail with attendees (excludes `missed` attendance from list).

### `GET /users?session_id={id}`

Users eligible for walk-in (excludes already on session).

### `GET /walkin/validate?field={phone|email}&value=...`

Async uniqueness check for new walk-in.

### `POST /sessions/{sessionId}/attendance/{bookingSessionId}`

Body: `{ "status": "attended" | "missed" }`

**Security:** Booking session MUST belong to `sessionId` or 422.

### `POST /sessions/{sessionId}/walkin/existing`

Body: `{ "user_ids": [1, 2] }`

### `POST /sessions/{sessionId}/walkin/new`

Body: `{ fullname, phone_number, email?, password? }` — creates user + walk-in attendance.

## Scheduled task

`php artisan sessions:send-reminders` — registered every 5 minutes in `routes/console.php`.

## Acceptance

- **Given** booking session for session A, **when** POST attendance with session B id, **then** 422.
- **Given** frozen staff, **when** accessing scheduler API, **then** 403.
