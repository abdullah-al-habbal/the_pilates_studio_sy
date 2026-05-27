<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent\Testimonial;

use App\Models\Testimonial;
use Illuminate\Database\Eloquent\Collection;

class TestimonialEloquentRepository
{
    public function __construct(private readonly Testimonial $model) {}

    public function getActiveOrdered(): Collection
    {
        return $this->model->newQuery()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }
}
