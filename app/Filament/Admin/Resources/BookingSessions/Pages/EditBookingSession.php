<?php

namespace App\Filament\Admin\Resources\BookingSessions\Pages;

use App\Filament\Admin\Resources\BookingSessions\BookingSessionResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditBookingSession extends EditRecord
{
    protected static string $resource = BookingSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
