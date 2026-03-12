<?php
// filePath: app/Filament/Admin/Resources/Bookings/Pages/ViewBooking.php

namespace App\Filament\Admin\Resources\Bookings\Pages;

use App\Filament\Admin\Resources\Bookings\BookingResource;
use Filament\Actions\{
    DeleteAction,
    EditAction,
    ForceDeleteAction,
    RestoreAction,
};
use Filament\Resources\Pages\ViewRecord;

class ViewBooking extends ViewRecord
{
    protected static string $resource = BookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label(__('dashboard.resources.bookings.actions.edit')),

            DeleteAction::make()
                ->label(__('dashboard.resources.bookings.actions.delete')),

            RestoreAction::make()
                ->label(__('dashboard.resources.bookings.actions.restore'))
                ->visible(fn($record): bool => $record->trashed()),

            ForceDeleteAction::make()
                ->label(__('dashboard.resources.bookings.actions.force_delete'))
                ->visible(fn($record): bool => $record->trashed()),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [];
    }
}
