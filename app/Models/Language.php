<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Collection;
class Language extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'direction',
        'is_active',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'is_active'  => 'boolean',
            'is_default' => 'boolean',
        ];
    }

    public function isRtl(): bool
    {
        return $this->direction === 'rtl';
    }

    public function userSettings(): HasMany
    {
        return $this->hasMany(UserSetting::class, 'preferred_language_id');
    }

    public static function getDefault(): ?self
    {
        return static::where('is_default', true)->where('is_active', true)->first();
    }

    public static function getActive(): Collection
    {
        return static::where('is_active', true)->orderBy('code')->get();
    }
}
