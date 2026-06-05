<?php
// app/Enums/UserRoleEnum.php
declare(strict_types=1);

namespace App\Enums;

enum UserRoleEnum: string
{
    case MAIN_ADMIN = 'main_admin';
    case ADMIN = 'admin';
    case CUSTOMER = 'customer';
    public function label(): string
    {
        return match ($this) {
            self::MAIN_ADMIN => 'Main Admin',
            self::ADMIN => 'Admin',
            self::CUSTOMER => 'Customer',
        };
    }
    public function color(): string
    {
        return match ($this) {
            self::MAIN_ADMIN => 'danger',
            self::ADMIN => 'warning',
            self::CUSTOMER => 'success',
        };
    }
}
