<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\ClassImage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

final class ClassImageObserver
{
    public function saving(ClassImage $image): void
    {
        if (! $image->is_primary || $image->class_id === null) {
            return;
        }

        ClassImage::query()
            ->where('class_id', $image->class_id)
            ->when($image->exists, fn ($query) => $query->whereKeyNot($image->getKey()))
            ->update(['is_primary' => false]);
    }

    public function updated(ClassImage $image): void
    {
        if (! $image->wasChanged('url')) {
            return;
        }

        $this->deleteAfterCommit($image->getRawOriginal('url'));
    }

    public function deleted(ClassImage $image): void
    {
        $this->deleteAfterCommit($image->url);
    }

    private function deleteAfterCommit(mixed $path): void
    {
        if (! is_string($path) || $path === '' || str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return;
        }

        DB::afterCommit(static function () use ($path): void {
            if (ClassImage::query()->where('url', $path)->exists()) {
                return;
            }

            Storage::disk('public')->delete($path);
        });
    }
}
