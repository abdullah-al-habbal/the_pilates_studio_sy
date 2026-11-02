# Deployment

Workflow: `.github/workflows/deploy.yml` (deploys to Hostinger over Git).

- Push `dev` -> dev server. Push `production` -> production server.
- `main` is the canonical copy of released code.
- `[fresh-migrate]` in the head commit message drops all tables and reseeds
  (DESTRUCTIVE). Never write it unless the user explicitly asks. Normal pushes
  preserve data.
- Never run `migrate:fresh` on production without that flag.
- A successful push does not mean the deploy succeeded; check CI separately.
- Health check: `/api/v1/public/app-version` on each host.
- Syncing branches and release commits: [release-sync.md](release-sync.md).
