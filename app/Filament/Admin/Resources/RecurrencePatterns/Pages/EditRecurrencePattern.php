<?php

namespace App\Filament\Admin\Resources\RecurrencePatterns\Pages;

use App\Filament\Admin\Resources\RecurrencePatterns\RecurrencePatternResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditRecurrencePattern extends EditRecord
{
    protected static string $resource = RecurrencePatternResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
