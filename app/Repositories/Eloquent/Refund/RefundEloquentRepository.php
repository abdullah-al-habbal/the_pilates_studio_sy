<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent\Refund;

use App\Models\Refund;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class RefundEloquentRepository
{
    public function create(array $data): Refund
    {
        return Refund::create($data);
    }

    public function getTotalsByCurrency(
        ?CarbonInterface $start = null,
        ?CarbonInterface $end = null,
    ): Collection {
        return Refund::query()
            ->selectRaw('currency_id, SUM(amount) as total')
            ->when($start, fn ($q) => $q->where('refunded_at', '>=', $start))
            ->when($end, fn ($q) => $q->where('refunded_at', '<=', $end))
            ->groupBy('currency_id')
            ->get()
            ->keyBy('currency_id');
    }
}
