<?php

namespace App\Filament\Admin\Resources\BookingSessions\Pages;

use App\Filament\Admin\Resources\BookingSessions\BookingSessionResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewBookingSession extends ViewRecord
{
    protected static string $resource = BookingSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
