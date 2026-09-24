---
name: release-sync
description: Commit verified project work, synchronize the production, dev, and main branches, and push the release branches safely. Use when the user asks to deploy, push all branches, or keep dev, production, and main aligned.
---

# Release Sync

Synchronize this Laravel application's deployment branches without losing work or silently releasing an unintended change.

## Deployment contract

- Pushing `dev` deploys the development server.
- Pushing `production` deploys the production server.
- `main` is the canonical copy of the current released code.
- `[fresh-migrate]` in a commit message performs a destructive reseed. Do not create or push such a commit unless the user explicitly requests it.

## Before committing

1. Confirm that the user explicitly requested a commit or push. A request to inspect, test, or edit code is not release authorization.
2. Run `git status --short`, `git branch --show-current`, `git remote -v`, and `git fetch origin --prune`.
3. Review the dirty-file list before staging. Preserve unrelated user changes. If the user explicitly asks to commit everything but the list contains an anomalous file (for example, an empty file with a malformed name), stop and ask whether it should be committed or removed; do not silently include it.
4. Run the relevant validation for the changed code. At minimum use `git diff --check` and the repository's required formatter (`./vendor/bin/pint` for PHP changes). State any unavailable test dependency rather than treating an unrun suite as passing.
5. Stage the authorized files, review `git diff --cached --check`, then make one clear commit. Never use `[fresh-migrate]` unless explicitly authorized.

## Synchronize branches

Use `production` as the release source only when it contains the approved release commit.

1. Compare histories with `git merge-base --is-ancestor dev production` and `git merge-base --is-ancestor main production`.
2. If both are ancestors, fast-forward them to production:

   ```bash
   git checkout dev
   git merge --ff-only production
   git checkout main
   git merge --ff-only production
   git checkout production
   ```

3. If either branch cannot fast-forward, do not force-push, reset, or create an automatic merge. Report the divergent commits and ask the user whether to merge a specified direction or keep the branches intentionally different.
4. Push only after all three local branches resolve to the intended commit:

   ```bash
   git push origin production dev main
   ```

5. Verify `production`, `dev`, `main`, and their `origin/*` tracking refs point at the same SHA. Report that SHA and the commit subject.

## Safety boundaries

- Never force-push, reset, or discard working-tree changes for this workflow.
- Do not include `public/storage`; it is a generated symlink and is gitignored.
- Do not claim CI/CD succeeded merely because a push succeeded. A push triggers deployment; inspect CI separately when the user asks for deployment status.
