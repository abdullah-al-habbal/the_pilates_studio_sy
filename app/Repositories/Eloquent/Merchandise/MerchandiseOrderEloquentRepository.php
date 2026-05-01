<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent\Merchandise;

use App\Models\MerchandiseOrder;
use App\Services\Currency\CurrencyService;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MerchandiseOrderEloquentRepository
{
    public function __construct(
        private readonly MerchandiseOrder $model,
        private readonly CurrencyService $currencyService
    ) {}

    public function getTotalRevenue(?CarbonInterface $startDate = null, ?CarbonInterface $endDate = null): float
    {
        $currencyId = $this->currencyService->getDefaultCurrency()->id;

        return (float) $this->model->newQuery()
            ->join('center_merchandises', 'merchandise_orders.merchandise_id', '=', 'center_merchandises.id')
            ->join('prices', function ($join) use ($currencyId) {
                $join->on('center_merchandises.id', '=', 'prices.priceable_id')
                     ->where('prices.priceable_type', 'App\Models\CenterMerchandise')
                     ->where('prices.currency_id', $currencyId);
            })
            ->when($startDate, fn ($q) => $q->where('merchandise_orders.created_at', '>=', $startDate))
            ->when($endDate, fn ($q) => $q->where('merchandise_orders.created_at', '<=', $endDate))
            ->sum(DB::raw('prices.amount * merchandise_orders.quantity'));
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
        $currencyId = $this->currencyService->getDefaultCurrency()->id;

        return $this->model->newQuery()
            ->select('merchandise_id', DB::raw('SUM(quantity) as total_quantity'))
            ->when($startDate, fn ($q) => $q->where('created_at', '>=', $startDate))
            ->when($endDate, fn ($q) => $q->where('created_at', '<=', $endDate))
            ->groupBy('merchandise_id')
            ->get()
            ->map(function ($order) use ($currencyId) {
                $merchandise = $order->merchandise;
                $price = $merchandise?->prices()->where('currency_id', $currencyId)->value('amount') ?? 0;

                return (object) [
                    'name' => $merchandise?->name ?? 'Product',
                    'quantity' => (int) $order->total_quantity,
                    'revenue' => (int) ($order->total_quantity * $price),
                ];
            })
            ->sortByDesc('revenue')
            ->take($limit);
    }
}
