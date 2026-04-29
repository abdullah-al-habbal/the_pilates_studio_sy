<?php

declare(strict_types=1);

namespace App\Enums;

enum BookingSourceTypeEnum: string
{
    case STANDARD      = 'standard';
    case FREEZE_ORIGIN = 'freeze_origin'; // original booking, now frozen
    case FREEZE_RESUME = 'freeze_resume'; // system-generated replacement
}
