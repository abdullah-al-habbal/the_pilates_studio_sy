# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

Pilates Studio SY: Laravel 12 + Filament v5 monolith (PHP 8.4+, MySQL) for
subscriptions, clients, store/POS, scheduler and multi-currency finance, with a
Sanctum mobile API. Where things live: `AGENTS.md`. Full index: `docs/index.md`.

## Most-used commands

```bash
composer run dev                 # server + queue + logs + vite
composer run test                # all tests
php artisan test --filter=Name   # single test
./vendor/bin/pint                # format before committing
```

## Rules (imported)

@docs/rules/non-negotiables.md
@docs/rules/layer-model.md
@docs/rules/images-and-storage.md
@docs/rules/deployment.md
@docs/rules/testing.md

Release commits and syncing `production`/`dev`/`main`: `docs/rules/release-sync.md`.
Known traps: `docs/ops/known-gaps.md`.
