<?php
// filePath: app/Enums/BookingStatusEnum.php

declare(strict_types=1);

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
    case FROZEN = 'frozen';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::ACTIVE => __('dashboard.resources.bookings.statuses.active'),
            self::EXHAUSTED => __('dashboard.resources.bookings.statuses.exhausted'),
            self::EXPIRED => __('dashboard.resources.bookings.statuses.expired'),
            self::CANCELLED => __('dashboard.resources.bookings.statuses.cancelled'),
            self::FROZEN => __('dashboard.resources.bookings.statuses.frozen'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::ACTIVE => 'success',
            self::EXHAUSTED => 'warning',
            self::EXPIRED => 'danger',
            self::CANCELLED => 'gray',
            self::FROZEN => 'info',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::ACTIVE => 'heroicon-o-check-circle',
            self::EXHAUSTED => 'heroicon-o-exclamation-triangle',
            self::EXPIRED => 'heroicon-o-x-circle',
            self::FROZEN => 'heroicon-o-lock-closed',
            self::CANCELLED => 'heroicon-o-no-symbol',
        };
    }

    public function label(): string
    {
        return $this->getLabel() ?? $this->value;
    }

    public function color(): string
    {
        return $this->getColor() ?? 'gray';
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
