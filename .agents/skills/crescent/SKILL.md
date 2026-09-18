---
name: crescent
description: Run the 7-phase CRESCENT state machine (Idea → Research → Prototype → PRD → Kanban → Ralph Loop → QA) to ship a feature, bug fix, or refactor end-to-end. Dispatches deterministically to the matching phase skill. Use when the user wants to start CRESCENT, ship a feature with the full process, or says "crescent", "start the state machine", "full process this feature", "7 phases".
---

# CRESCENT — 7-Phase Feature Delivery State Machine

CRESCENT is a strict state machine. You enter at Phase 1 and advance one phase at a time. You do NOT skip, reorder, or merge phases. Each phase has an entry artifact on disk, an exit artifact on disk, and a single dispatch target.

## State Machine Law

1. **No phase may be skipped.** Phase N requires Phase N-1's exit artifact at the canonical path on disk. If missing → return to N-1.
2. **No phase runs twice in a session without an explicit reset.** A reset is the user typing `crescent reset to <phase>` AND a one-line entry in `NOTES.md` recording why.
3. **The current phase is named at the top of every reply** while CRESCENT is active. Format: `[CRESCENT · Phase N · <Phase name>]`.
4. **Phase transitions are explicit and announced.** When a phase's exit artifact is written, announce `Exiting Phase N → entering Phase N+1`, then dispatch.
5. **Context discipline.** Between phases, do not carry forward speculation, scratch notes, or partial drafts. The exit artifact is the only thing that crosses the boundary.
6. **Idea (Phase 1) is the user's prompt.** It is not skipped — it is acknowledged, slugged, and written down.

## Phases

| # | Phase      | Entry artifact                       | Skill dispatched                       | Exit artifact                                                                  |
| - | ---------- | ------------------------------------ | -------------------------------------- | ------------------------------------------------------------------------------ |
| 1 | Idea       | (user prompt)                        | — (inline in this skill)               | `docs/<JIRA-ID>/<feature-slug>/IDEA.md`                                        |
| 2 | Research   | IDEA.md                              | [research](../research/SKILL.md)       | `.../research.md` — or `Research skipped: no externals` noted in IDEA.md       |
| 3 | Prototype  | IDEA.md (+ research.md if present)   | [prototype](../prototype/SKILL.md)     | colocated prototype code + a `NOTES.md` capturing the answer to the question   |
| 4 | PRD        | Phase 3 exit                         | [to-prd](../to-prd/SKILL.md), grilled by [grill-me](../grill-me/SKILL.md) | `.../PRD.md` + Jira parent epic body                                            |
| 5 | Kanban     | PRD.md                               | [to-issues](../to-issues/SKILL.md)     | `.../kanban.md` (index of issues) + GitLab issues at `gitlab.zadapps.info`     |
| 6 | Ralph Loop | kanban.md                            | [ralph-loop](../ralph-loop/SKILL.md)   | merged MRs + closed issues                                                     |
| 7 | QA         | Phase 6 exit                         | [qa-plan](../qa-plan/SKILL.md)         | `.../qa-plan.md` + new GitLab issues for gaps                                  |

Phases 6 and 7 may loop (QA produces tickets → Ralph executes → QA again) until the user marks the feature shipped.

## Entry procedure

When invoked:

1. Confirm or derive the **feature slug** (kebab-case) and **Jira epic ID** (uppercase). If either is unknown, ask the user — do NOT invent.
2. Create `docs/<JIRA-ID>/<feature-slug>/` if it does not exist.
3. Write `IDEA.md` using the template below.
4. Announce: `[CRESCENT · Phase 1 · Idea] — IDEA.md written. Exiting Phase 1 → entering Phase 2.`
5. Dispatch the `research` skill.

<idea-template>
# Idea

**Jira:** <JIRA-ID>
**Slug:** <feature-slug>
**Type:** feature | bug | refactor
**Created:** <YYYY-MM-DD>

## In the user's words

(verbatim copy of the user's prompt; do not paraphrase)

## What this changes for users

One short paragraph.

## Open questions

A short list. These get answered during Research, Prototype, and PRD — not now.
</idea-template>

## Phase preconditions (the gate that prevents skipping)

Before dispatching any phase N ≥ 2, the orchestrator verifies — by reading the disk, not memory:

<preconditions>
- Phase 2 (Research):   IDEA.md exists at the canonical path
- Phase 3 (Prototype):  IDEA.md exists; research.md exists OR IDEA.md records `Research skipped: no externals`
- Phase 4 (PRD):        Phase 3 exit (`prototype/NOTES.md` answering the prototype's question) exists
- Phase 5 (Kanban):     PRD.md exists AND has a non-empty `## User Stories` section
- Phase 6 (Ralph Loop): kanban.md exists AND lists at least one GitLab issue ID at `gitlab.zadapps.info`
- Phase 7 (QA):         kanban.md shows zero open issues
</preconditions>

If a precondition fails, do NOT dispatch. State the missing artifact and return to the phase that produces it.

## Exit

When Phase 7 produces zero new gaps AND the human walks the QA plan to all-PASS, announce `[CRESCENT · COMPLETE] — <JIRA-ID>/<feature-slug>` and stop. Do not auto-restart.
