<?php

namespace Database\Factories\Concerns;

use App\Exceptions\SeederDependencyMissingException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

trait CopiesSourceImage
{
    protected function copySourceImage(string $sourcePath, string $basePath, ?string $identifier = null, string $fileSuffix = ''): string
    {
        if (! File::exists($sourcePath)) {
            throw new SeederDependencyMissingException("Required source image not found at: {$sourcePath}");
        }

        $now = now();
        $year = $now->format('Y');
        $month = $now->format('m');
        $day = $now->format('d');
        $timestamp = $now->timestamp;
        $ext = pathinfo($sourcePath, PATHINFO_EXTENSION);

        $filename = $fileSuffix ? "{$timestamp}-{$fileSuffix}" : (string) $timestamp;

        $folder = $identifier ? "{$basePath}/{$identifier}" : $basePath;

        $relativePath = "{$folder}/{$year}/{$month}/{$day}/{$filename}.{$ext}";

        Storage::disk('public')->put($relativePath, File::get($sourcePath));

        return $relativePath;
    }
}
