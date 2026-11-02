<?php

declare(strict_types=1);

namespace App\Http\Actions\Web\Admin\Scheduler;

use App\Models\ClassCategory;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;

final class GetCategoriesAction
{
    use ApiResponseTrait;

    public function __invoke(): JsonResponse
    {
        $locale = app()->getLocale();

        $categories = ClassCategory::query()
            ->orderBy('id')
            ->get()
            ->map(fn (ClassCategory $category): array => [
                'id' => $category->id,
                'name' => $category->getTranslation('name', $locale, false) ?: $category->getTranslation('name', 'en'),
            ])
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        return $this->success($categories);
    }
}
