# A11 — Idempotency is net-new

| | |
|---|---|
| **Verdict** | PRD §9.3 has no existing foundation |
| **Impact** | Medium |
| **Status** | Accepted |

## What the code does

No idempotency token, request-deduplication cache, or replay guard exists anywhere in the
codebase. Double-submit protection today is purely visual: `.btn-single-action` plus
`.btn-spinner`, handled in `public/js/operations/main.js`.

## Consequence

PRD §9.3 (frontend-generated UUID checked server-side) must be built from scratch.

Cheapest correct implementation:

```php
if (! Cache::add("backfill:{$token}", true, 300)) {
    throw ValidationException::withMessages([...]);
}
```

`Cache::add()` is atomic, so it doubles as the lock.

**Verify before shipping:** the production `CACHE_STORE`. The test environment uses `array`
(`phpunit.xml:25`), which is per-process and would make the guard a no-op across requests.
