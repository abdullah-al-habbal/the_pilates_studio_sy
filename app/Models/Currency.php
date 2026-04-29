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
    protected $fillable = ['code','name','symbol','is_active'];
    protected function casts(): array { return ['is_active'=>'boolean']; }
    public function packagePrices(): HasMany { return $this->hasMany(PackagePrice::class); }
    public function bookings(): HasMany { return $this->hasMany(Booking::class); }
    public static function findByCode(string $code): ?self { return static::where('code',strtoupper($code))->where('is_active',true)->first(); }
}
