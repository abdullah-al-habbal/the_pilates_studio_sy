<?php

declare(strict_types=1);

namespace App\Enums;

use Carbon\CarbonInterface;
use Filament\Support\Contracts\HasLabel;

/**
 * Days of the week, stored as readable lowercase English names.
 *
 * The stored value is intentionally the English name (never a number) so the
 * persisted data stays readable and stable across locales. Display labels come
 * from getLabel(); the numeric Carbon day is derived on demand and never stored.
 */
enum WeekdayEnum: string implements HasLabel
{
    case SUNDAY = 'sunday';
    case MONDAY = 'monday';
    case TUESDAY = 'tuesday';
    case WEDNESDAY = 'wednesday';
    case THURSDAY = 'thursday';
    case FRIDAY = 'friday';
    case SATURDAY = 'saturday';

    public function getLabel(): string
    {
        return __('dashboard.weekdays.'.$this->value);
    }

    /**
     * Carbon's dayOfWeek value (0 = Sunday … 6 = Saturday).
     */
    public function carbonDayOfWeek(): int
    {
        return match ($this) {
            self::SUNDAY => CarbonInterface::SUNDAY,
            self::MONDAY => CarbonInterface::MONDAY,
            self::TUESDAY => CarbonInterface::TUESDAY,
            self::WEDNESDAY => CarbonInterface::WEDNESDAY,
            self::THURSDAY => CarbonInterface::THURSDAY,
            self::FRIDAY => CarbonInterface::FRIDAY,
            self::SATURDAY => CarbonInterface::SATURDAY,
        };
    }

    public static function fromCarbonDayOfWeek(int $dayOfWeek): self
    {
        foreach (self::cases() as $case) {
            if ($case->carbonDayOfWeek() === $dayOfWeek) {
                return $case;
            }
        }

        throw new \InvalidArgumentException("Unknown day of week: {$dayOfWeek}");
    }

    /**
     * @return array<string, string> value => label, for Filament selects
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->getLabel()])
            ->toArray();
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Normalise a raw stored/submitted array into unique, ordered enum cases.
     *
     * Unknown values are discarded here — validation is responsible for
     * rejecting them with a message before this point is reached.
     *
     * @param  iterable<mixed>|null  $raw
     * @return list<self>
     */
    public static function normalise(?iterable $raw): array
    {
        if ($raw === null) {
            return [];
        }

        $seen = [];

        foreach ($raw as $value) {
            $case = $value instanceof self
                ? $value
                : (is_string($value) ? self::tryFrom(strtolower(trim($value))) : null);

            if ($case !== null) {
                $seen[$case->value] = $case;
            }
        }

        // Return in canonical Sunday-first order rather than submission order.
        return array_values(array_filter(
            self::cases(),
            fn (self $case) => isset($seen[$case->value])
        ));
    }
}
