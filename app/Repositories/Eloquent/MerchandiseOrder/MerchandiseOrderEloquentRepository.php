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

    /**
     * @deprecated Use getRevenueByCurrency() for per-currency accuracy
     */
    public function getTotalRevenueByCurrency(
        int $currencyId,
        ?CarbonInterface $startDate = null,
        ?CarbonInterface $endDate = null,
    ): int {
        return (int) MerchandiseOrder::query()
            ->where('currency_id', $currencyId)
            ->when($startDate, fn($q) => $q->where('ordered_at', '>=', $startDate))
            ->when($endDate, fn($q) => $q->where('ordered_at', '<=', $endDate))
            ->sum('paid_amount');
    }

    /**
     * Returns revenue grouped by currency_id — NO joins to prices table.
     * Uses only stored paid_amount + currency_id for historical accuracy.
     */
    public function getRevenueByCurrency(
        ?CarbonInterface $startDate = null,
        ?CarbonInterface $endDate = null,
    ): Collection {
        return MerchandiseOrder::query()
            ->selectRaw('currency_id, SUM(paid_amount) as total, COUNT(*) as count')
            ->whereNotNull('paid_amount')
            ->when($startDate, fn($q) => $q->where('ordered_at', '>=', $startDate))
            ->when($endDate, fn($q) => $q->where('ordered_at', '<=', $endDate))
            ->groupBy('currency_id')
            ->get()
            ->map(fn($item) => (object) [
                'currency_id' => (int) $item->currency_id,
                'total_revenue' => (int) $item->total,
                'order_count' => (int) $item->count,
            ]);
    }

    /**
     * Returns revenue with historical exchange rate snapshot for base-currency conversion.
     */
    public function getRevenueWithExchangeSnapshot(
        ?CarbonInterface $startDate = null,
        ?CarbonInterface $endDate = null,
    ): Collection {
        return MerchandiseOrder::query()
            ->selectRaw('
                currency_id,
                SUM(paid_amount) as total,
                COUNT(*) as count,
                AVG(exchange_rate_snapshot) as avg_snapshot_rate
            ')
            ->whereNotNull('paid_amount')
            ->whereNotNull('exchange_rate_snapshot')
            ->when($startDate, fn($q) => $q->where('ordered_at', '>=', $startDate))
            ->when($endDate, fn($q) => $q->where('ordered_at', '<=', $endDate))
            ->groupBy('currency_id')
            ->get()
            ->map(fn($item) => (object) [
                'currency_id' => (int) $item->currency_id,
                'total_revenue' => (int) $item->total,
                'order_count' => (int) $item->count,
                'avg_exchange_rate_snapshot' => $item->avg_snapshot_rate ? (float) $item->avg_snapshot_rate : null,
            ]);
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
        return MerchandiseOrder::query()
            ->select([
                'merchandise_orders.merchandise_id',
                DB::raw('SUM(merchandise_orders.quantity) as quantity'),
                DB::raw('SUM(merchandise_orders.paid_amount) as revenue'),
            ])
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
