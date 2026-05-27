<?php

declare(strict_types=1);

namespace App\ValueObjects\Landing;

use App\Models\StaticPage;
use Illuminate\Support\Collection;

class LandingDataVO
{
    public function __construct(
        public readonly LandingSettingsVO $settings,
        /** @var Collection<int, LandingClassVO> */
        public readonly Collection $classes,
        /** @var Collection<int, LandingScheduleDayVO> */
        public readonly Collection $schedule,
        /** @var Collection<int, LandingInstructorVO> */
        public readonly Collection $instructors,
        /** @var Collection<int, LandingPackageVO> */
        public readonly Collection $packages,
        /** @var Collection<int, LandingTestimonialVO> */
        public readonly Collection $testimonials,
        /** @var Collection<int, StaticPage> */
        public readonly Collection $staticPages,
        public readonly bool $hasError,
        public readonly bool $isEmpty,
    ) {}
}


