<?php
declare(strict_types=1);
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class PackagePrice extends Model
{
    use HasFactory;
    protected $fillable = ['package_id','currency_id','amount'];
    protected function casts(): array { return ['amount'=>'integer']; }
    public function package(): BelongsTo { return $this->belongsTo(Package::class); }
    public function currency(): BelongsTo { return $this->belongsTo(Currency::class); }
}
