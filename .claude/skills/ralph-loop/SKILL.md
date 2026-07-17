---
name: ralph-loop
description: Execute a kanban of GitLab issues against the codebase in a Ralph loop — sequentially by default, parallel for non-blocking AFK tickets via git worktrees. Each iteration picks the next issue, implements it, runs the project verification gate, commits, opens an MR, and only then advances. Use when the user wants to start execution, run the Ralph loop, work through the kanban AFK, or says "ralph this", "run the loop", "execute the tickets", "start phase 6".
---

# Ralph Loop

A Ralph loop is **a deterministic execution loop** over a kanban of independently-grabbable issues. One issue per iteration. No iteration starts until the previous one has either merged or been explicitly parked. The loop is AFK-safe: a human can walk away and the agent produces coherent, reviewable work.

## Preconditions (refuse to start if any fail)

<preconditions>
1. `docs/<JIRA-ID>/<feature-slug>/kanban.md` exists and references at least one open GitLab issue at `gitlab.zadapps.info`.
2. `glab auth status -h gitlab.zadapps.info` returns authenticated.
3. The working tree is clean, OR the only diff is CRESCENT artifacts under `docs/<JIRA-ID>/<feature-slug>/`.
4. The current branch is a CRESCENT branch (`<type>/<JIRA-ID>-<feature-slug>`) OR the user has explicitly named the target branch.
</preconditions>

If any precondition fails, surface which one and stop. Do not "best-effort" past a missing precondition.

## The loop

```
while there is an unblocked, open issue on the kanban:
  reload kanban.md from disk
  pick next issue (lowest GitLab issue ID among unblocked & open)
  announce [CRESCENT · Phase 6 · Ralph · issue #N]
  implement the issue
  run the verification gate (see below)
  if verification fails: debug, re-verify, do NOT advance
  commit with the format below
  push, open MR via `glab mr create`, link the issue
  mark the issue 'in review' on kanban.md
  await merge (or, if user is AFK and auto-merge is on, advance)
  mark the issue 'closed' on kanban.md
end
```

**Reload `kanban.md` from disk every iteration.** Earlier iterations or the user may have edited it. Trusting an in-memory copy is the most common Ralph-loop failure mode.

## Verification gate per iteration

A green local run is required before opening the MR. CI green is required before advancing to the next issue.

Per `wp-cms/CLAUDE.md`:

- `./sites/site/vendor/bin/phpcs` (always)
- `./sites/site/vendor/bin/phpunit --bootstrap=tests/bootstrap.php --testsuite=unit tests/unit` (always)
- Integration suite if the touched plugin has integration tests
- Plus whatever the issue's acceptance criteria checklist demands

For monorepo work outside `wp-cms/`:

- Pulumi: `npx vitest` or `npx jest` per `pulumi/CLAUDE.md`
- AI engine: bun-based runner per `ai/engine/CLAUDE.md`
- Frontends: pnpm per `frontends/nextjs/CLAUDE.md`

## Commit format

```
<type>(<area>): <imperative summary, ≤72 chars>

<body — what changed and why, wrap at 80>

Closes <JIRA-ID>
Refs https://gitlab.zadapps.info/<group>/<repo>/-/issues/<N>
```

`<type>` ∈ `feat | fix | refactor | test | docs | chore`. `<area>` is the plugin or component touched (e.g. `zad-islamqa-core`, `pulumi/components/db`).

## MR format

`glab mr create` with:

- **Title:** `[<JIRA-ID>] <imperative summary>`
- **Description must include:**
  - A link to `docs/<JIRA-ID>/<feature-slug>/`
  - `Closes #N` for the GitLab issue
  - A reference to the Jira epic

## Parallelization (optional)

Multiple AFK issues with no blocking dependency between them MAY be run in parallel, each in its own git worktree, by spawning Agent subagents with `isolation: "worktree"`. Each subagent runs one iteration against one issue and returns when its MR is open. The orchestrator then resumes the sequential loop with the remaining issues.

Constraints:

- **Do not parallelize HITL issues.**
- **Do not parallelize when fewer than three independent issues remain** — coordination overhead exceeds the gain.
- **Do not parallelize issues that touch the same plugin or the same Pulumi component** — even if the kanban says they don't block each other, the merge contention does.

## Exit

When `kanban.md` shows zero open issues, announce `[CRESCENT · Phase 6 · COMPLETE] — N issues merged. Exiting Phase 6 → entering Phase 7.` and dispatch the `qa-plan` skill.

## Anti-patterns

- **Do not** rewrite the kanban from scratch during a Ralph iteration. Phase 5 owns the kanban shape; Phase 6 only marks state.
- **Do not** open an MR that closes more than one issue unless they are tightly blocking-related and the merge was strictly required.
- **Do not** advance on a yellow/flaky CI. Re-run, diagnose, or park the issue back to Phase 5.
- **Do not** skip `phpcs` because "it's a tiny change." Project rules in `wp-cms/CLAUDE.md` forbid suppression.
