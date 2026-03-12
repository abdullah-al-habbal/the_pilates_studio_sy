<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum BookingSessionStatusEnum: string implements HasColor, HasIcon, HasLabel
{
    case RESERVED = 'reserved';
    case CANCELLED = 'cancelled';
    case ATTENDED = 'attended';
    case NO_SHOW = 'no_show';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::RESERVED => __('dashboard.resources.booking_sessions.statuses.reserved'),
            self::CANCELLED => __('dashboard.resources.booking_sessions.statuses.cancelled'),
            self::ATTENDED => __('dashboard.resources.booking_sessions.statuses.attended'),
            self::NO_SHOW => __('dashboard.resources.booking_sessions.statuses.no_show'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::RESERVED => 'info',
            self::CANCELLED => 'danger',
            self::ATTENDED => 'success',
            self::NO_SHOW => 'warning',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::RESERVED => 'heroicon-o-calendar',
            self::CANCELLED => 'heroicon-o-x-circle',
            self::ATTENDED => 'heroicon-o-check-circle',
            self::NO_SHOW => 'heroicon-o-question-mark-circle',
        };
    }

    public static function toArray(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value', 'value');
    }

    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->getLabel();
        }
        return $options;
    }
}
