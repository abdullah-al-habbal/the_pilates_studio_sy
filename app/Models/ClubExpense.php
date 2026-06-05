<?php
declare(strict_types=1);

namespace App\Models;

use App\Enums\ClubExpenseStatusEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClubExpense extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'currency_id',
        'amount',
        'notes',
        'recorded_by',
        'expense_date',
        'status',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'expense_date' => 'date',
            'status' => ClubExpenseStatusEnum::class,
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ClubExpenseCategory::class, 'category_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }
}
