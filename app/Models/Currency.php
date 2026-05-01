<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class Currency extends Model
{
    use HasFactory, HasTranslations;
    public array $translatable = ['name'];
    protected $fillable = ['code', 'name', 'symbol', 'decimal_places', 'exchange_rate', 'is_active'];
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'decimal_places' => 'integer',
            'exchange_rate' => 'float',
        ];
    }
    public function prices(): HasMany
    {
        return $this->hasMany(Price::class);
    }
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
    public static function findByCode(string $code): ?self
    {
        return static::where('code', strtoupper($code))->where('is_active', true)->first();
    }
}
