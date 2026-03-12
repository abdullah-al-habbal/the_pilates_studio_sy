<?php

namespace App\Filament\Admin\Resources\BookingSessions\Pages;

use App\Filament\Admin\Resources\BookingSessions\BookingSessionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBookingSession extends CreateRecord
{
    protected static string $resource = BookingSessionResource::class;
}
