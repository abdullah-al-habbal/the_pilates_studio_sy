<?php
declare(strict_types=1);

namespace App\Http\Actions\Web\Admin\Operations;

use App\Models\ClubExpenseCategory;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;

final class GetExpenseCategoriesAction
{
    use ApiResponseTrait;

    public function __invoke(): JsonResponse
    {
        $categories = ClubExpenseCategory::pluck('name');
        return $this->success(data: $categories->toArray());
    }
}
