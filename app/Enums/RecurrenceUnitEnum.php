<?php

declare(strict_types=1);

namespace App\Enums;

enum RecurrenceUnitEnum: string
{
    case DAY = 'day';
    case WEEK = 'week';
    case MONTH = 'month';
}
