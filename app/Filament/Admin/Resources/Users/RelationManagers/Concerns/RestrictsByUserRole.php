<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Users\RelationManagers\Concerns;

use App\Enums\UserRoleEnum;
use Illuminate\Database\Eloquent\Model;

trait RestrictsByUserRole
{
    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        $role = $ownerRecord->role instanceof UserRoleEnum
            ? $ownerRecord->role->value
            : (string) $ownerRecord->role;

        return in_array($role, static::$allowedOwnerRoles, true)
            && parent::canViewForRecord($ownerRecord, $pageClass);
    }
}
