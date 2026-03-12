<?php
// filePath: app/Enums/BookingStatusEnum.php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum BookingStatusEnum: string implements HasColor, HasIcon, HasLabel
{
    case ACTIVE = 'active';
    case EXHAUSTED = 'exhausted';
    case EXPIRED = 'expired';
    case CANCELLED = 'cancelled';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::ACTIVE => __('dashboard.resources.bookings.statuses.active'),
            self::EXHAUSTED => __('dashboard.resources.bookings.statuses.exhausted'),
            self::EXPIRED => __('dashboard.resources.bookings.statuses.expired'),
            self::CANCELLED => __('dashboard.resources.bookings.statuses.cancelled'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::ACTIVE => 'success',
            self::EXHAUSTED => 'warning',
            self::EXPIRED => 'danger',
            self::CANCELLED => 'gray',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::ACTIVE => 'heroicon-o-check-circle',
            self::EXHAUSTED => 'heroicon-o-exclamation-triangle',
            self::EXPIRED => 'heroicon-o-x-circle',
            self::CANCELLED => 'heroicon-o-no-symbol',
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
