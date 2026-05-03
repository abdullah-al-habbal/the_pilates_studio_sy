<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Refund extends Model
{
    use HasFactory;

    protected $fillable = [
        'refundable_type',
        'refundable_id',
        'user_id',
        'currency_id',
        'amount',
        'reason',
        'refunded_by',
        'refunded_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'refunded_at' => 'datetime',
        ];
    }

    public function refundable(): MorphTo
    {
        return $this->morphTo();
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function refundedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'refunded_by');
    }

    public function isBookingRefund(): bool
    {
        return $this->refundable_type === (new Booking())->getTable();
    }

    public function isStoreRefund(): bool
    {
        return $this->refundable_type === (new MerchandiseOrder())->getTable();
    }

    public function getRefundableTitleAttribute(): string
    {
        return match ($this->refundable_type) {
            'bookings' => "Booking #{$this->refundable_id}",
            'merchandise_orders' => "Order #{$this->refundable_id}",
            default => "Unknown #{$this->refundable_id}",
        };
    }
}
