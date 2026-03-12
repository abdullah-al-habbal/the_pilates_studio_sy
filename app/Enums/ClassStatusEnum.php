<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum ClassStatusEnum: string implements HasColor, HasIcon, HasLabel
{
    case ACTIVE   = 'active';
    case INACTIVE = 'inactive';
    case ARCHIVED = 'archived';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::ACTIVE   => __('dashboard.resources.classes.statuses.active'),
            self::INACTIVE => __('dashboard.resources.classes.statuses.inactive'),
            self::ARCHIVED => __('dashboard.resources.classes.statuses.archived'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::ACTIVE   => 'success',
            self::INACTIVE => 'warning',
            self::ARCHIVED => 'gray',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::ACTIVE   => 'heroicon-o-check-circle',
            self::INACTIVE => 'heroicon-o-pause-circle',
            self::ARCHIVED => 'heroicon-o-archive-box',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->getLabel()])
            ->toArray();
    }
}
