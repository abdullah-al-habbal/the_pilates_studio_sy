<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RecurrenceUnitEnum;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\Attributes\Translatable;
use Spatie\Translatable\HasTranslations;

#[Fillable(['name', 'label', 'interval_days', 'frequency_unit', 'frequency_interval'])]
#[Translatable(['label'])]
class RecurrencePattern extends Model
{
    use HasFactory, HasTranslations;

    protected function casts(): array
    {
        return [
            'interval_days' => 'integer',
            'frequency_unit' => RecurrenceUnitEnum::class,
            'frequency_interval' => 'integer',
        ];
    }

    public function resolvedFrequencyUnit(): RecurrenceUnitEnum
    {
        // Some legacy call sites deliberately select only the original recurrence
        // columns. Accessing the casted property in that case throws when Laravel
        // is configured to reject missing attributes, so inspect raw loaded
        // attributes before using the new optional columns.
        $frequencyUnit = $this->getAttributes()['frequency_unit'] ?? null;

        if (is_string($frequencyUnit) && ($unit = RecurrenceUnitEnum::tryFrom($frequencyUnit)) !== null) {
            return $unit;
        }

        return match ($this->name) {
            'weekly', 'biweekly' => RecurrenceUnitEnum::WEEK,
            'monthly' => RecurrenceUnitEnum::MONTH,
            default => RecurrenceUnitEnum::DAY,
        };
    }

    public function resolvedFrequencyInterval(): int
    {
        $frequencyInterval = $this->getAttributes()['frequency_interval'] ?? null;

        if ((int) $frequencyInterval > 0) {
            return (int) $frequencyInterval;
        }

        return match ($this->name) {
            'daily', 'weekly', 'monthly' => 1,
            'biweekly' => 2,
            default => max(1, (int) $this->interval_days),
        };
    }

    public function classes(): HasMany
    {
        return $this->hasMany(Classes::class);
    }
}
