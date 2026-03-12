<?php

namespace App\Models;

use App\Enums\ClassStatusEnum;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Spatie\Translatable\HasTranslations;

/**
 * @method string getTranslation(string $key, string $locale = null)
 * @method void setTranslation(string $key, string $locale, string $value)
 */
class Classes extends Model
{
    use HasFactory, SoftDeletes, HasTranslations;

    public array $translatable = ['title', 'about'];

    protected $fillable = [
        'instructor_id',
        'class_category_id',
        'recurrence_pattern_id',
        'title',
        'about',
        'start_time',
        'end_time',
        'start_date',
        'end_date',
        'total_spots',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'start_date'  => 'date',
            'end_date'    => 'date',
            'total_spots' => 'integer',
            'status'      => ClassStatusEnum::class,
        ];
    }

    public function isActive(): bool
    {
        return $this->status === ClassStatusEnum::ACTIVE;
    }

    protected function durationMinutes(): Attribute
    {
        return Attribute::make(
            get: fn() => (int) Carbon::parse($this->start_time)
                ->diffInMinutes(Carbon::parse($this->end_time))
        );
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(Instructor::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ClassCategory::class, 'class_category_id');
    }

    public function recurrencePattern(): BelongsTo
    {
        return $this->belongsTo(RecurrencePattern::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ClassImage::class, 'class_id');
    }

    public function primaryImage(): HasOne
    {
        return $this->hasOne(ClassImage::class, 'class_id')->where('is_primary', true);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(ClassSession::class, 'class_id')->orderBy('date')->orderBy('start_time');
    }
}
