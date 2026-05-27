<?php

declare(strict_types=1);

namespace App\ValueObjects\Landing;

use App\Models\Testimonial;

class LandingTestimonialVO
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $role,
        public readonly string $quote,
        public readonly string $avatar,
        public readonly int $rating,
    ) {}

    public static function fromModel(Testimonial $testimonial): self
    {
        return new self(
            id: $testimonial->id,
            name: $testimonial->getTranslation('name', app()->getLocale()),
            role: $testimonial->getTranslation('role', app()->getLocale()) ?? '',
            quote: $testimonial->getTranslation('quote', app()->getLocale()),
            avatar: $testimonial->avatar ?? 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&w=100&q=80',
            rating: $testimonial->rating,
        );
    }
}
