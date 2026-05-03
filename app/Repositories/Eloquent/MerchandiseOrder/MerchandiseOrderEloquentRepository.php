<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent\MerchandiseOrder;

use App\Models\CenterMerchandise;
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
    ) {
    }

    public function create(array $data): MerchandiseOrder
    {
        return $this->model->create($data);
    }

    public function findOrFail(int $id): MerchandiseOrder
    {
        return $this->model->newQuery()->findOrFail($id);
    }

    public function delete(int $id): bool
    {
        return (bool) $this->model->where('id', $id)->delete();
    }

    public function getTotalRevenueByCurrency(
        int $currencyId,
        ?CarbonInterface $startDate = null,
        ?CarbonInterface $endDate = null,
    ): int {
        return (int) MerchandiseOrder::query()
            ->join('prices', function ($join) use ($currencyId): void {
                $join->on('prices.priceable_id', '=', 'merchandise_orders.merchandise_id')
                    ->where('prices.priceable_type', '=', CenterMerchandise::class)
                    ->where('prices.currency_id', '=', $currencyId);
            })
            ->when($startDate, fn($q) => $q->where('merchandise_orders.ordered_at', '>=', $startDate))
            ->when($endDate, fn($q) => $q->where('merchandise_orders.ordered_at', '<=', $endDate))
            ->sum(DB::raw('merchandise_orders.quantity * prices.amount'));
    }

    public function getTotalRevenue(?CarbonInterface $startDate = null, ?CarbonInterface $endDate = null): int
    {
        $defaultCurrencyId = $this->currencyService->getDefaultCurrency()->id;

        return $this->getTotalRevenueByCurrency($defaultCurrencyId, $startDate, $endDate);
    }

    public function getTotalCount(?CarbonInterface $startDate = null, ?CarbonInterface $endDate = null): int
    {
        return MerchandiseOrder::query()
            ->when($startDate, fn($q) => $q->where('ordered_at', '>=', $startDate))
            ->when($endDate, fn($q) => $q->where('ordered_at', '<=', $endDate))
            ->count();
    }

    public function getTopSellingSummary(
        int $limit = 5,
        ?CarbonInterface $startDate = null,
        ?CarbonInterface $endDate = null
    ): Collection {
        $defaultCurrencyId = $this->currencyService->getDefaultCurrency()->id;

        return MerchandiseOrder::query()
            ->select([
                'merchandise_orders.merchandise_id',
                DB::raw('SUM(merchandise_orders.quantity) as quantity'),
                DB::raw('SUM(merchandise_orders.quantity * prices.amount) as revenue'),
            ])
            ->join('prices', function ($join) use ($defaultCurrencyId) {
                $join->on('prices.priceable_id', '=', 'merchandise_orders.merchandise_id')
                    ->where('prices.priceable_type', '=', CenterMerchandise::class)
                    ->where('prices.currency_id', '=', $defaultCurrencyId);
            })
            ->when($startDate, fn($q) => $q->where('merchandise_orders.ordered_at', '>=', $startDate))
            ->when($endDate, fn($q) => $q->where('merchandise_orders.ordered_at', '<=', $endDate))
            ->groupBy('merchandise_orders.merchandise_id')
            ->orderByDesc('revenue')
            ->limit($limit)
            ->get()
            ->map(function ($item) {
                $merch = CenterMerchandise::find($item->merchandise_id);
                $item->name = $merch?->name ?? [];
                return $item;
            });
    }
}
