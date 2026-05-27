<?php

declare(strict_types=1);

namespace App\ValueObjects\Landing;

use Illuminate\Support\Collection;

class LandingDataVO
{
    public function __construct(
        public readonly LandingSettingsVO $settings,
        public readonly Collection $classes,
        public readonly Collection $schedule,
        public readonly Collection $instructors,
        public readonly Collection $packages,
        public readonly Collection $testimonials,
        public readonly Collection $staticPages,
        public readonly bool $hasError,
        public readonly bool $isEmpty,
    ) {}
}
