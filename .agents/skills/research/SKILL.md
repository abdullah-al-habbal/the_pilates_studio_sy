---
name: research
description: Cache external research (third-party APIs, unfamiliar protocols, vendor SDKs, hard-to-explore code regions) into a single research.md asset scoped to the current feature, so future agent sessions don't re-explore the same ground. Use when the user mentions integrating an external API, building against unfamiliar docs, working with a legacy/sprawling module, or says "research this", "cache the research", "I'll need an external API".
---

# Research

A research cache is a **sprint-lifetime asset**. It captures findings about external systems that an AFK agent would otherwise re-discover on every context window. It is NOT a general knowledge base — when the feature ships, the cache is deleted or absorbed into a durable doc.

## When to run — and when to skip

Run if the work touches any of:

- A third-party API the codebase does not already integrate with
- An unfamiliar protocol, file format, or wire schema
- A vendor SDK or service whose docs are non-trivial to navigate
- A sprawling or legacy region of the codebase that takes long to map (legacy plugins, generated code)

Skip if the work is entirely local to well-trodden code. Record the skip in `IDEA.md` under `## Open questions` as a single line: `Research skipped: no external dependencies.` That line is the exit artifact — the orchestrator looks for it.

## Process

1. **Identify the unknowns.** Extract from IDEA.md the specific questions that require external lookup. Phrase them as questions, not commands. If the list is empty, exit with the "skipped" line.
2. **Answer each question** with the minimum evidence to act on it. Use WebFetch / WebSearch for external docs; Read / Grep / Explore agent for codebase regions. Cite every source inline.
3. **Stop at "good enough to start the prototype."** More research = more rot. Defer the rest to Phase 4.
4. **Write the cache** to `docs/<JIRA-ID>/<feature-slug>/research.md` using the template below.
5. **Announce exit** and return control to the `crescent` orchestrator.

<research-template>
# Research — <JIRA-ID> / <feature-slug>

**Captured:** <YYYY-MM-DD>
**Expires:** end of this feature's sprint — do not consume after that date without re-verification.

## Question 1: <verbatim question>

**Answer:** <one paragraph>

**Evidence:**
- <URL> — <what it told us>
- <path/file.ts:42> — <what it told us>

**Confidence:** high | medium | low. If low, note what would raise it.

## Question 2: ...

## What we did NOT research (and why)

A short list of questions deferred to the prototype or PRD phase, with one-line reasons.
</research-template>

## Rules

1. **No code in research.md.** Prose, citations, and minimal interface signatures only. Implementation belongs to the prototype.
2. **One file per feature.** Never split into `research-auth.md` + `research-api.md`. Long is fine.
3. **External URLs must be live URLs**, not paraphrases. The next agent must be able to re-verify.
4. **Mark the expiry date.** A research cache without an expiry is a trap.
5. **Cite repo paths with line numbers** (`plugins/zad-x/src/Foo.php:42`) so a future agent can re-anchor if the file moved.
