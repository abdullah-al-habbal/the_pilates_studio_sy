<?php
declare(strict_types=1);
namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum AttendanceStatusEnum: string implements HasColor, HasIcon, HasLabel
{
    case ATTENDED = 'attended';
    case MISSED = 'missed';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::ATTENDED => __('dashboard.resources.booking_sessions.attendance.attended'),
            self::MISSED => __('dashboard.resources.booking_sessions.attendance.missed'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::ATTENDED => 'success',
            self::MISSED => 'warning',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::ATTENDED => 'heroicon-o-check-circle',
            self::MISSED => 'heroicon-o-clock',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn(self $case) => [$case->value => $case->getLabel()])
            ->all();
    }
}