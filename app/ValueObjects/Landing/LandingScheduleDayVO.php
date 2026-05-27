<?php

declare(strict_types=1);

namespace App\ValueObjects\Landing;

use Illuminate\Support\Collection;

class LandingScheduleDayVO
{
    public function __construct(
        public readonly string $date,
        public readonly string $dayName,
        public readonly Collection $sessions,
    ) {}
}
