<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin\Operations;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DailyBalanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'booking_revenue'  => $this->resource['booking_revenue'],
            'store_revenue'    => $this->resource['store_revenue'],
            'total_revenue'    => $this->resource['total_revenue'],
            'total_refunds'    => $this->resource['total_refunds'],
            'total_expenses'   => $this->resource['total_expenses'],
            'true_balance'     => $this->resource['true_balance'],
            'date'             => $this->resource['date'],
        ];
    }
}
