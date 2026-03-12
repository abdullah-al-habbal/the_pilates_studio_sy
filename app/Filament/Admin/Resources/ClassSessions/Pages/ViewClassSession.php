<?php

namespace App\Filament\Admin\Resources\ClassSessions\Pages;

use App\Filament\Admin\Resources\ClassSessions\ClassSessionResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewClassSession extends ViewRecord
{
    protected static string $resource = ClassSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
