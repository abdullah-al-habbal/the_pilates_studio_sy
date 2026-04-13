<?php
declare(strict_types=1);
namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

/**
 * Tracks the reservation lifecycle only.
 * Attendance is tracked separately via AttendanceStatusEnum on the same model.
 */
enum BookingSessionStatusEnum: string implements HasColor, HasIcon, HasLabel
{
    case RESERVED = 'reserved';
    case CANCELLED = 'cancelled';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::RESERVED => __('dashboard.resources.booking_sessions.statuses.reserved'),
            self::CANCELLED => __('dashboard.resources.booking_sessions.statuses.cancelled'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::RESERVED => 'info',
            self::CANCELLED => 'danger',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::RESERVED => 'heroicon-o-calendar',
            self::CANCELLED => 'heroicon-o-x-circle',
        };
    }

    public static function toArray(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn(self $case) => [$case->value => $case->getLabel()])
            ->all();
    }
}