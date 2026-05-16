<?php
// app\Models\MerchandiseOrder.php
declare(strict_types=1);
namespace App\Models;

use App\Services\Currency\PricingService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read float|null $exchange_rate_snapshot Immutable rate at transaction time for audit accuracy
 */
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
        'exchange_rate_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'ordered_at' => 'datetime',
            'paid_amount' => 'integer',
            'exchange_rate_snapshot' => 'float',
        ];
    }

    public function getTotalPriceAttribute(): int
    {
        if ($this->paid_amount !== null) {
            return $this->paid_amount;
        }

        if ($this->merchandise) {
            $pricing = app(PricingService::class);
            $basePrice = $pricing->getBasePrice($this->merchandise);
            if ($basePrice !== null && $this->currency_id) {
                return $pricing->calculateAmount($basePrice * $this->quantity, $this->currency_id);
            }
        }

        return 0;
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