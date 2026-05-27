<?php

declare(strict_types=1);

namespace App\ValueObjects\Landing;

use App\Models\Instructor;

class LandingInstructorVO
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $title,
        public readonly string $specialty,
        public readonly string $bio,
        public readonly string $imageUrl,
        public readonly array $socialLinks,
    ) {}

    public static function fromModel(Instructor $instructor): self
    {
        return new self(
            id: $instructor->id,
            name: $instructor->getTranslation('name', app()->getLocale()),
            title: $instructor->getTranslation('title', app()->getLocale()) ?? '',
            specialty: $instructor->getTranslation('specialty', app()->getLocale()) ?? '',
            bio: $instructor->getTranslation('bio', app()->getLocale()) ?? '',
            imageUrl: $instructor->image ?: 'https://images.unsplash.com/photo-1518611012118-696072aa579a?auto=format&fit=crop&w=400&q=80',
            socialLinks: $instructor->social_links ?? [],
        );
    }
}
