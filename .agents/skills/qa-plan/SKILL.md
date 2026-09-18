---
name: qa-plan
description: Produce a human-runnable QA plan from completed CRESCENT work and feed any gaps back to the kanban as new GitLab issues. Re-enters the Ralph loop until QA produces zero gaps. Use when the user wants to QA finished features, generate a manual test plan, hand off to a human reviewer, or says "generate QA", "write the QA plan", "time for QA", "start phase 7".
---

# QA Plan

After the Ralph loop closes the last issue, the feature is **code-complete** — not **shipped**. The QA plan exists so a human can walk the feature end-to-end with confidence, and so anything the agent could not have verified is surfaced before the feature reaches users.

## Preconditions

Refuse to write the QA plan unless:

<preconditions>
1. `docs/<JIRA-ID>/<feature-slug>/kanban.md` shows zero open issues.
2. `docs/<JIRA-ID>/<feature-slug>/PRD.md` exists and has a non-empty `## User Stories` section.
3. The merged commits for this feature are queryable: `glab issue list --search "[<JIRA-ID>]" --state closed` returns the closed issues.
</preconditions>

## Process

1. **Re-read the PRD's user stories.** Each story is the seed for one QA case.
2. **Walk the closed kanban issues.** For each, derive the smallest manual action a human can take to confirm the user-visible behaviour. Skip issues whose behaviour is purely internal (refactors, build chores) — note them under "Not human-QA-able" so they aren't forgotten, but don't manufacture fake test cases.
3. **Identify gaps.** Anything in the PRD that no kanban issue covered → these become new GitLab issues and reopen Phase 5 / Phase 6.
4. **Write `docs/<JIRA-ID>/<feature-slug>/qa-plan.md`** using the template below.
5. **Create follow-up GitLab issues** via `glab issue create` for every gap, with title prefix `[<JIRA-ID>] QA gap:`.
6. **Announce exit.**

<qa-plan-template>
# QA Plan — <JIRA-ID> / <feature-slug>

**Generated:** <YYYY-MM-DD>
**PRD:** [PRD.md](./PRD.md)
**Closed issues:** N

## How to run this QA

This plan is for a human. Read the PRD first. Then walk each case below in order. Mark each PASS / FAIL / BLOCKED. Anything FAIL or BLOCKED opens a follow-up GitLab issue using the template at the bottom.

Environment: the dev compose stack, started with `docker compose --env-file <project>.env watch`. Reset the DB between cases that mutate state.

## Cases

### Case 1: <user story verbatim>

**Source:** PRD user story #X (+ kanban issue #N if directly covered)

**Steps:**
1. <observable step>
2. <observable step>

**Expected:** <user-visible outcome>

**Verifies:** <which acceptance criteria from which issue, if any>

### Case 2: ...

## Not human-QA-able

Internal-only changes that were merged but cannot be exercised through the UI / API. Listed for traceability:

- Issue #N: <one line>

## Gaps found by the agent

PRD requirements no kanban issue addressed. Each becomes a new GitLab issue:

- <one line per gap, with the GitLab issue URL the agent just opened>

## Follow-up issue template (for failed cases)

```
[<JIRA-ID>] QA gap: <what failed or was missing>

**Found during:** Phase 7 QA of <feature-slug>
**PRD requirement:** <quote the line from PRD.md>
**Repro:** <if FAIL — exact steps the human took>

## Acceptance criteria
- [ ] ...
```
</qa-plan-template>

## Loop semantics

If the QA plan produces zero gaps AND the human walks the plan with all PASS → the feature is shipped. The `crescent` orchestrator announces `[CRESCENT · COMPLETE]` and stops.

If gaps exist, the new GitLab issues land on `kanban.md` and the orchestrator re-enters **Phase 6** (Ralph loop). Then **Phase 7 runs again**. This 6↔7 loop is bounded only by reality — most features loop once, occasionally twice. If the loop reaches three iterations, stop and ask the user whether the PRD is still correct.

## Anti-patterns

- **Do not** write QA cases for code paths that no kanban issue produced. The PRD is the source of truth; gaps go to new issues, never to "the QA plan will catch it later."
- **Do not** auto-pass a case because the agent ran it. Cases are for humans by definition.
- **Do not** delete this plan when the feature ships. It is the receipt that the feature was QA'd, and it lives forever under `docs/<JIRA-ID>/<feature-slug>/`.
