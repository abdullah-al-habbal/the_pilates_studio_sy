<?php

namespace App\Filament\Admin\Resources\BookingSessions\Pages;

use App\Filament\Admin\Resources\BookingSessions\BookingSessionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBookingSessions extends ListRecords
{
    protected static string $resource = BookingSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
