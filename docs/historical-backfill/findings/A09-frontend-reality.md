# A09 — Frontend reality vs PRD §11

| | |
|---|---|
| **Verdict** | PRD overstates how much is reusable |
| **Impact** | Medium — changes the Phase 5 estimate |
| **Status** | Accepted |

## No stepper or wizard exists

Nothing in the Operations Hub implements a multi-step flow with progress indicator or
prev/next. The closest analogue is the Quick Sale modal (`public/js/operations/modules/store.js:115-248`),
which toggles the visibility of two divs via `window.toggleSaleMode('existing'|'walkin')`.

PRD §11.2 (five-step indicator, click-to-edit previous steps) is **entirely net-new**.

## Pagination is cursor + infinite scroll, not offset

The only paginated-list-inside-a-modal pattern is the sale customer picker
(`store.js:335-361`), mirrored by the notifications user picker:

- state object holds `users[]`, `nextCursor`, `hasMore`, `isLoading`
- scroll listener at `store.js:306-312` fires when `scrollTop + clientHeight >= scrollHeight - 40`
- endpoint returns `{data, meta: {next_cursor, has_more}}` via `OperationsAPI.getClientsCursor`

PRD §3.3 says "cursor/offset". Use **cursor** to match.

## `window.__()` re-parses its payload on every call

```php
// resources/views/layouts/operations.blade.php:171-174
window.__ = function (key) {
    var t = @json($operationsTranslations);   // re-inlined per call
    return t[key] || key;
};
```

`$operationsTranslations` is `Arr::dot(__('dashboard.operations_ui'))`. Adding ~40 backfill keys
enlarges the object that **every existing `__()` call** re-materialises. Hoist the literal out
of the closure while adding keys.

## Other mechanics

| Concern | Reality |
|---|---|
| Script loading | Plain `<script>` for `api.js` and `ui.js`; one `type="module"` `main.js`. **Not** vite. |
| Modal DOM | `#modal-overlay` / `#modal-container` exist at `layouts/operations.blade.php:156-161` |
| Confirms | SweetAlert2 |
| Toast | `OperationsUI.toast(message, type)` — renders into HTML, so `\n` collapses |
| Lang path | `resources/lang/{en,ar}/dashboard.php` — **not** `lang/` |
| Key parity | `operations_ui` is exactly 226/226 EN/AR. Overall `dashboard.php` has 61 keys missing in AR, none under `operations_ui`. |
