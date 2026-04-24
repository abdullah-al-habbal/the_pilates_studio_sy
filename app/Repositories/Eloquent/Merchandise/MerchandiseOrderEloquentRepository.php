<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent\Merchandise;

use App\Models\MerchandiseOrder;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MerchandiseOrderEloquentRepository
{
    public function __construct(
        private readonly MerchandiseOrder $model
    ) {}

    public function getTotalRevenue(?CarbonInterface $startDate = null, ?CarbonInterface $endDate = null): float
    {
        return (float) $this->model->newQuery()
            ->join('center_merchandises', 'merchandise_orders.merchandise_id', '=', 'center_merchandises.id')
            ->when($startDate, fn ($q) => $q->where('merchandise_orders.created_at', '>=', $startDate))
            ->when($endDate, fn ($q) => $q->where('merchandise_orders.created_at', '<=', $endDate))
            ->sum(DB::raw('center_merchandises.price * merchandise_orders.quantity'));
    }

    public function getTotalCount(?CarbonInterface $startDate = null, ?CarbonInterface $endDate = null): int
    {
        return $this->model->newQuery()
            ->when($startDate, fn ($q) => $q->where('created_at', '>=', $startDate))
            ->when($endDate, fn ($q) => $q->where('created_at', '<=', $endDate))
            ->count();
    }

    public function getTopSellingSummary(int $limit = 5, ?CarbonInterface $startDate = null, ?CarbonInterface $endDate = null): Collection
    {
        return $this->model->newQuery()
            ->with('merchandise')
            ->select('merchandise_id', DB::raw('SUM(quantity) as total_quantity'))
            ->when($startDate, fn ($q) => $q->where('created_at', '>=', $startDate))
            ->when($endDate, fn ($q) => $q->where('created_at', '<=', $endDate))
            ->groupBy('merchandise_id')
            ->get()
            ->map(function ($order) {
                return (object) [
                    'name' => $order->merchandise?->name ?? 'Product',
                    'quantity' => (int) $order->total_quantity,
                    'revenue' => (int) ($order->total_quantity * ($order->merchandise?->price ?? 0)),
                ];
            })
            ->sortByDesc('revenue')
            ->take($limit);
    }
}
