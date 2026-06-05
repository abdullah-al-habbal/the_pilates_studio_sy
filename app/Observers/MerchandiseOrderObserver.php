<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\CenterMerchandise;
use App\Models\MerchandiseOrder;
use InvalidArgumentException;

final class MerchandiseOrderObserver
{
    public function creating(MerchandiseOrder $order): void
    {
        $merchandise = CenterMerchandise::find($order->merchandise_id);

        if (!$merchandise) {
            throw new InvalidArgumentException('Selected merchandise does not exist.');
        }

        if ($merchandise->stock_quantity < $order->quantity) {
            throw new InvalidArgumentException(
                "Insufficient stock. Available: {$merchandise->stock_quantity}, Requested: {$order->quantity}"
            );
        }

        $order->merchandise_name_snapshot = $merchandise->getTranslations('name');

        $price = $merchandise->getPriceForCurrency($order->currency_id)
            ?? $merchandise->getBasePrice();

        $order->merchandise_unit_price_snapshot = $price;
    }

    public function deleting(MerchandiseOrder $order): void
    {
        $merchandise = $order->merchandise;

        if ($merchandise) {
            $merchandise->increment('stock_quantity', $order->quantity);
        }
    }
}
