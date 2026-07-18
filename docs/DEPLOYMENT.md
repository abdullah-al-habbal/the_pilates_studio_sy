# Deployment Guide

## How Deploy Works

CI/CD triggers on push to `dev` or `production` branches via `.github/workflows/deploy.yml`.

### Deploy Phases

1. **Storage prep** — Backs up `storage/app/` (protects admin uploads)
2. **Git sync** — `git reset --hard` + `git clean -fd`
3. **Restore storage** — Rsyncs backup back (images preserved)
4. **Storage symlink** — Creates `public/storage → ../storage/app/public`
5. **Dependencies** — `composer install` + `npm ci && npm run build`
6. **Migrations** — `migrate --force` (normal) or `migrate:fresh --seed` (if `[fresh-migrate]` in commit)
7. **Cache** — `optimize:clear` + config/route/view cache

### Data Preservation

| Push type | Database | Uploaded files |
|-----------|----------|----------------|
| Normal push | Existing data untouched (additive migrations only) | Backed up and restored |
| `[fresh-migrate]` push | **DROPPED** and re-seeded | Backed up and restored |

**Normal pushes are always safe.** Only `[fresh-migrate]` destroys data.

## Commands

### Normal Deploy (safe, no data loss)

```bash
git push origin dev        # → dev server
git push origin production # → production server
```

### Re-seed Deploy (DESTRUCTIVE — drops all tables)

```bash
git commit --allow-empty -m "chore: reseed [fresh-migrate]"
git push origin production
```

### Manual Emergency (SSH)

```bash
ssh hostinger-pilates
cd ~/domains/pilatesstudiosy.com/public_html/production

# Fix broken symlink
rm -f public/storage && ln -s ../storage/app/public public/storage

# Re-seed (drops all data)
php artisan migrate:fresh --seed --force

# Verify images
php artisan tinker --execute="echo \App\Models\ClassImage::first()->image_url;"
curl -sI https://pilatesstudiosy.com/storage/class-images/1/$(ls storage/app/public/class-images/1/ | head -1)/$(find storage/app/public/class-images/1/ -type f | head -1 | xargs basename) | head -3
```

## Storage & Images

### Directory Structure

```
storage/app/public/           ← All uploaded images live here
├── class-images/             ← Class gallery (Seeder + Filament)
├── instructors/              ← Instructor profiles (Seeder)
├── static-pages/             ← Static page images (Seeder)
├── testimonials/             ← Testimonial avatars (Seeder)
└── app-settings/             ← Hero, logo, etc. (Seeder + Filament)

public/storage → ../storage/app/public   ← Symlink (created by deploy)
```

### How Image URLs Work

All models use `Storage::disk('public')->url($path)` to generate URLs:

```
DB: class-images/1/2026/07/18/123-primary.webp
    ↓ Storage::disk('public')->url()
URL: /storage/class-images/1/2026/07/18/123-primary.webp
    ↓ public/storage → ../storage/app/public
File: storage/app/public/class-images/1/2026/07/18/123-primary.webp
```

### Why `artisan storage:link` Fails

Hostinger disables `exec()` in PHP. The `storage:link` artisan command requires it.
The deploy script creates the symlink manually: `ln -s ../storage/app/public public/storage`

**Critical:** The symlink MUST use `../storage/app/public` (relative path).
Using `storage/app/public` creates a circular symlink ("too many levels of symbolic links").

### Models & Accessors

| Model | Accessor | What it does |
|-------|----------|-------------|
| `ClassImage` | `getImageUrlAttribute()` | `Storage::disk('public')->url($this->url)` |
| `Instructor` | `getImageUrlAttribute()` | `Storage::disk('public')->url($this->image)` |
| `StaticPage` | `getImageUrlAttribute()` | `Storage::disk('public')->url($this->image)` |
| `Testimonial` | `getAvatarUrlAttribute()` | `Storage::disk('public')->url($this->avatar)` |
| `AppSetting` | None (raw value) | `Storage::disk('public')->url($value)` in Resource/VO |

### Consumers

| Layer | File | How it resolves |
|-------|------|----------------|
| Blade (landing) | `_classes.blade.php` | `LandingClassVO->imageUrl` → `ClassImage->image_url` |
| Blade (landing) | `_hero.blade.php` | `LandingSettingsVO->heroImage` |
| Blade (landing) | `_header.blade.php` | `LandingSettingsVO->logoUrl` |
| Blade (static) | `show.blade.php` | `StaticPage->image_url` |
| API | `ClassImageResource` | `ClassImage->image_url` |
| API | `AppSettingResource` | `Storage::disk('public')->url($value)` |
| Filament | `ClassesInfolist` | `ImageEntry::make('image_url')->state(fn($r) => $r->image_url)` |
| Filament | `ImagesRelationManager` | `ImageEntry::make('image_url')->disk('public')->state(fn($r) => $r->url)` |
| Filament | `InstructorInfolist` | `ImageEntry::make('image')` (defaults to `public` disk) |

## Troubleshooting

### Images return 404

```bash
# 1. Check symlink
ls -la public/storage
# Should show: public/storage -> ../storage/app/public

# 2. Check file exists
ls storage/app/public/class-images/1/

# 3. Recreate symlink if broken
rm -f public/storage && ln -s ../storage/app/public public/storage

# 4. Verify URL
curl -sI https://pilatesstudiosy.com/storage/class-images/1/.../primary.webp
```

### Symlink shows "too many levels of symbolic links"

The symlink uses wrong path. Fix:
```bash
rm -f public/storage
ln -s ../storage/app/public public/storage   # ../ NOT storage/
```

### Deploy didn't create symlink

Check deploy logs for the `🔗 Storage symlink created` line. If missing, the deploy script may be outdated. Pull latest `deploy.yml`.
