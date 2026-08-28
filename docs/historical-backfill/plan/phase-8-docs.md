# Phase 8 — Docs and formatting

## Steps

### 8.1 Update the spec docs

| File | Add |
|---|---|
| `docs/specs/operations-api.md` | the two new endpoints under a Bookings/Backfill heading, with the response envelope |
| `docs/specs/operations-ui.md` | the backfill modal flow, its step model, and the new `OperationsAPI` methods |
| `docs/specs/route-blade-js-matrix.md` | two rows: route → blade → JS |

### 8.2 Close this workspace's open items

Mark A02, A03, A05 and A13 resolved in:

- [`../README.md`](../README.md) — status table and open-decisions table
- [`../findings/README.md`](../findings/README.md) — status column
- [`../decisions/README.md`](../decisions/README.md) — move each from Pending into the log

### 8.3 Formatting

```bash
./vendor/bin/pint
```

## Done when

- The three spec docs describe the shipped behaviour.
- No finding in this folder is still marked Open.
- `pint` reports no changes.
