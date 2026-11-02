# AGENTS.md

Entry point for AI agents. Keep this file and `CLAUDE.md` under 50 lines; put
detail in `docs/` and link to it.

## Reading order

1. `CLAUDE.md`: commands and the imported rules
2. `docs/map/repository.md`: where code lives
3. `docs/map/domain.md`: bookings, refunds, currency model
4. `docs/ops/known-gaps.md`: traps to avoid

## Docs

| Need | File |
|------|------|
| Standards | `docs/rules/non-negotiables.md`, `docs/rules/layer-model.md` |
| Images (`Storage::disk('public')->url()` only) | `docs/rules/images-and-storage.md` |
| Deploy, `[fresh-migrate]` | `docs/rules/deployment.md` |
| Commit and branch sync | `docs/rules/release-sync.md` |
| Tests | `docs/rules/testing.md` |
| Commands, schedule | `docs/ops/commands.md` |

## Graph

If `graphify-out/graph.json` exists, try `graphify query "<question>"` before
raw search, and run `graphify update .` after code changes. It does not exist
in the repo today.
