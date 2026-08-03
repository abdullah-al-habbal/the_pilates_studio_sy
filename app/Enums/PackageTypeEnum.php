<?php

declare(strict_types=1);

namespace App\Enums;

enum PackageTypeEnum: string
{
    case STANDARD = 'standard';
    case BY_SYSTEM = 'by_system';
    case FOR_FREEZE_CLIENT = 'for_freeze_client';
    case WALK_IN = 'walk_in';

    public function label(): string
    {
        return match ($this) {
            self::STANDARD => 'Standard',
            self::BY_SYSTEM => 'System Generated',
            self::FOR_FREEZE_CLIENT => 'Unfreeze Residual',
            self::WALK_IN => 'Walk-in',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::STANDARD => 'gray',
            self::BY_SYSTEM => 'info',
            self::FOR_FREEZE_CLIENT => 'warning',
            self::WALK_IN => 'success',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])->toArray();
    }
}
