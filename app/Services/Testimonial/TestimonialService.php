<?php

declare(strict_types=1);

namespace App\Services\Testimonial;

use App\Repositories\Eloquent\Testimonial\TestimonialEloquentRepository;
use Illuminate\Database\Eloquent\Collection;

class TestimonialService
{
    public function __construct(private readonly TestimonialEloquentRepository $repository) {}

    public function getActiveTestimonials(): Collection
    {
        return $this->repository->getActiveOrdered();
    }
}
