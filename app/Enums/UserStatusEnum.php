<?php

declare(strict_types=1);

namespace App\Enums;

enum UserStatusEnum: string
{
    case ACTIVE = 'active';
    case FROZEN = 'frozen';
    case DEACTIVATED = 'deactivated';

    public function label(): string
    {
        return match($this) {
            self::ACTIVE => 'Active',
            self::FROZEN => 'Frozen',
            self::DEACTIVATED => 'Deactivated',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::ACTIVE => 'success',
            self::FROZEN => 'info',
            self::DEACTIVATED => 'danger',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn($c) => [$c->value => $c->label()])->toArray();
    }

    public function getIcon(): ?string
    {
        return match($this) {
            self::ACTIVE      => 'heroicon-o-check-circle',
            self::FROZEN      => 'heroicon-o-lock-closed',
            self::DEACTIVATED => 'heroicon-o-x-circle',
        };
    }
}