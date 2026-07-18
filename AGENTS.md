## graphify

This project has a knowledge graph at graphify-out/ with god nodes, community structure, and cross-file relationships.

When the user types `/graphify`, invoke the `skill` tool with `skill: "graphify"` before doing anything else.

Rules:
- For codebase questions, first run `graphify query "<question>"` when graphify-out/graph.json exists. Use `graphify path "<A>" "<B>"` for relationships and `graphify explain "<concept>"` for focused concepts. These return a scoped subgraph, usually much smaller than GRAPH_REPORT.md or raw grep output.
- Dirty graphify-out/ files are expected after hooks or incremental updates; dirty graph files are not a reason to skip graphify. Only skip graphify if the task is about stale or incorrect graph output, or the user explicitly says not to use it.
- If graphify-out/wiki/index.md exists, use it for broad navigation instead of raw source browsing.
- Read graphify-out/GRAPH_REPORT.md only for broad architecture review or when query/path/explain do not surface enough context.
- After modifying code, run `graphify update .` to keep the graph current (AST-only, no API cost).

## Storage & Images

All images are stored in `storage/app/public/` and served via the `public/storage` symlink.

**NEVER** use `url()`, `asset()`, or `public_path()` for image URLs. Always use:
```php
Storage::disk('public')->url($path)
```

**NEVER** write files directly to `public/data-images/` or `public/storage/`. Always use:
```php
Storage::disk('public')->put($path, $contents);
```

**NEVER** use `php artisan storage:link` — Hostinger disables `exec()`. The deploy script creates the symlink automatically.

Image paths in the DB are relative to `storage/app/public/`:
```
DB: class-images/1/2026/07/18/123-primary.webp
URL: /storage/class-images/1/2026/07/18/123-primary.webp
```

Models with image accessors: `ClassImage`, `Instructor`, `StaticPage`, `Testimonial`.
All use `Storage::disk('public')->url()` — do NOT change to `url()` or `asset()`.

See `docs/DEPLOYMENT.md` for full storage architecture and troubleshooting.

## Deployment

- Push to `dev` → deploys to dev server
- Push to `production` → deploys to production server
- `[fresh-migrate]` in commit message → drops all tables and re-seeds (DESTRUCTIVE)
- Normal pushes are always safe — admin data is preserved

**NEVER** run `migrate:fresh` on production without `[fresh-migrate]` flag.
**NEVER** commit `public/storage` — it's a symlink, gitignored.

See `docs/DEPLOYMENT.md` for full deployment guide.

## Coding Standards

- PHP 8.4+, strict types
- Laravel 12 conventions
- Filament v5 for admin panel
- All image URLs via `Storage::disk('public')->url()`
- Run `./vendor/bin/pint` before committing
