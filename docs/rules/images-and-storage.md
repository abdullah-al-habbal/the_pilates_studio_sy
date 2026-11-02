# Images and storage

Files live in `storage/app/public/`, served through the `public/storage` symlink.
DB paths are relative to that root, e.g. `class-images/1/2026/07/18/123-primary.webp`.

- URLs: `Storage::disk('public')->url($path)` only. Never `url()`, `asset()`,
  `public_path()` for images; they break on production.
- Writes: `Storage::disk('public')->put($path, $contents)`. Never write straight
  into `public/data-images/` or `public/storage/`.
- Never run `php artisan storage:link` (Hostinger disables `exec()`); the deploy
  script creates the link. Local fix: `rm -f public/storage && ln -s ../storage/app/public public/storage`
- Models with image accessors: `ClassImage`, `Instructor`, `StaticPage`, `Testimonial`.
  API resource: `app/Http/Resources/Api/V1/ClassImageResource.php`.

New image field: add the column, add an accessor returning
`Storage::disk('public')->url($this->image)`, use `ImageEntry::make('image')` in
Filament (public disk by default), expose `'image_url' => $this->image_url` in the resource.

404 debug: `ls -la public/storage`, `ls storage/app/public/<path>`,
`curl -sI https://<domain>/storage/<path>`.
