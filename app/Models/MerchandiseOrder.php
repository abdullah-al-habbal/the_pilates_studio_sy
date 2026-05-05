<?php
declare(strict_types=1);
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MerchandiseOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'merchandise_id',
        'quantity',
        'customer_id',
        'ordered_at',
        'currency_id',
        'paid_amount',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'ordered_at' => 'datetime',
            'paid_amount' => 'integer',
        ];
    }

    public function getTotalPriceAttribute(): int
    {
        if ($this->paid_amount !== null) {
            return $this->paid_amount;
        }
        return ($this->merchandise?->price ?? 0) * $this->quantity;
    }

    public function merchandise(): BelongsTo
    {
        return $this->belongsTo(CenterMerchandise::class, 'merchandise_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

}