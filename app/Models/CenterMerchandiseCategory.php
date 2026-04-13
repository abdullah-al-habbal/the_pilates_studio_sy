<?php
declare(strict_types=1);
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class CenterMerchandiseCategory extends Model
{
    use HasFactory, SoftDeletes, HasTranslations;

    public array $translatable = ['name'];

    protected $fillable = ['name'];

    public function merchandises(): HasMany
    {
        return $this->hasMany(CenterMerchandise::class, 'category_id');
    }
}