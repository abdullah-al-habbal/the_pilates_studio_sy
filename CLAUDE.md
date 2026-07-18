# CLAUDE.md — AI Agent Instructions

## Project Context

Laravel 12 + Filament v5 monolith for pilates studio management. PHP 8.4+, strict types.

## Key Conventions

### Image URLs — CRITICAL
All image URLs MUST use `Storage::disk('public')->url()`:
```php
// ✅ CORRECT
Storage::disk('public')->url($this->image)

// ❌ WRONG — breaks on production
url($this->image)
asset($this->image)
public_path($this->image)
```

Files live in `storage/app/public/`, served via `public/storage → ../storage/app/public` symlink.

**NEVER** use `php artisan storage:link` — Hostinger disables `exec()`.

### Coding Standards
- Run `./vendor/bin/pint` before committing
- PHP 8.4+ strict types
- Laravel 12 patterns (Actions → Handlers → Services → Repositories)
- Filament v5 for admin panel
- Translatable models via Spatie

### Deployment
- Push to `dev` → dev server
- Push to `production` → production server
- `[fresh-migrate]` in commit message → destructive reseed
- Normal pushes are always safe

## File Locations

| What | Where |
|------|-------|
| Image models | `app/Models/ClassImage.php`, `Instructor.php`, `StaticPage.php`, `Testimonial.php` |
| Image resources | `app/Http/Resources/Api/V1/ClassImageResource.php` |
| Filament image CRUD | `app/Filament/Admin/Resources/Classes/RelationManagers/ImagesRelationManager.php` |
| Storage config | `config/filesystems.php` (public disk) |
| Deploy script | `.github/workflows/deploy.yml` |
| Deployment docs | `docs/DEPLOYMENT.md` |

## Common Tasks

### Adding a new image field to a model
1. Add `image` column to migration
2. Add accessor: `return Storage::disk('public')->url($this->image)`
3. In Filament: `ImageEntry::make('image')` (defaults to public disk)
4. In API resource: `'image_url' => $this->image_url`

### Debugging 404 images
```bash
# Check symlink
ls -la public/storage

# Recreate if broken
rm -f public/storage && ln -s ../storage/app/public public/storage

# Check file exists
ls storage/app/public/{path-to-image}

# Check URL
curl -sI https://domain.com/storage/{path-to-image}
```
