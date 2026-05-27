<?php

declare(strict_types=1);

namespace App\ValueObjects\Landing;

use App\Models\Classes;

class LandingClassVO
{
    public function __construct(
        public readonly int $id,
        public readonly string $title,
        public readonly ?string $categoryName,
        public readonly ?string $categorySlug,
        public readonly ?string $instructorName,
        public readonly int $durationMinutes,
        public readonly ?string $imageUrl,
        public readonly int $availableSpots,
    ) {}

    public static function fromModel(Classes $class): self
    {
        $primaryImage = $class->primaryImage?->image_url
            ?? 'https://ui-avatars.com/api/?name=Class&size=400&background=059669&color=fff';
        return new self(
            id: $class->id,
            title: $class->getTranslation('title', app()->getLocale()) ?? $class->title,
            categoryName: $class->category?->getTranslation('name', app()->getLocale()),
            categorySlug: $class->category?->slug,
            instructorName: $class->instructor?->getTranslation('name', app()->getLocale()),
            durationMinutes: $class->duration_minutes,
            imageUrl: $primaryImage,
            availableSpots: $class->total_spots,
        );
    }
}
