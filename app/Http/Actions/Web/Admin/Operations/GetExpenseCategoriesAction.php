<?php
declare(strict_types=1);

namespace App\Http\Actions\Web\Admin\Operations;

use App\Repositories\Eloquent\ClubExpenseCategory\ClubExpenseCategoryEloquentRepository;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;

final class GetExpenseCategoriesAction
{
    use ApiResponseTrait;

    public function __invoke(ClubExpenseCategoryEloquentRepository $categoryRepository): JsonResponse
    {
        return $this->success(data: $categoryRepository->getAllNames());
    }
}
