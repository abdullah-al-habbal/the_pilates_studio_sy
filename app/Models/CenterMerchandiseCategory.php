<?php

declare(strict_types=1);

namespace App\Models;

use App\Observers\CenterMerchandiseCategoryObserver;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\Attributes\Translatable;
use Spatie\Translatable\HasTranslations;

#[Fillable(['name'])]
#[ObservedBy([CenterMerchandiseCategoryObserver::class])]
#[Translatable(['name'])]
class CenterMerchandiseCategory extends Model
{
    use HasFactory, HasTranslations, SoftDeletes;

    public function merchandises(): HasMany
    {
        return $this->hasMany(CenterMerchandise::class, 'category_id');
    }
}
