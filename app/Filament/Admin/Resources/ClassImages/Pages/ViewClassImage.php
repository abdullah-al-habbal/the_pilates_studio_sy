<?php

namespace App\Filament\Admin\Resources\ClassImages\Pages;

use App\Filament\Admin\Resources\ClassImages\ClassImageResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewClassImage extends ViewRecord
{
    protected static string $resource = ClassImageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
