<?php

declare(strict_types=1);

namespace App\Handlers\Admin\Operations;

use App\Repositories\Eloquent\ClubExpense\ClubExpenseEloquentRepository;
use Illuminate\Support\Collection;

final readonly class GetExpenseBreakdownHandler
{
    public function __construct(
        private ClubExpenseEloquentRepository $repository
    ) {}

    public function handle(string $date): Collection
    {
        return $this->repository->getBreakdownByDate($date);
    }
}
