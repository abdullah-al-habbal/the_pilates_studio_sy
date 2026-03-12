<?php

namespace App\Filament\Admin\Resources\ClassImages\Pages;

use App\Filament\Admin\Resources\ClassImages\ClassImageResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditClassImage extends EditRecord
{
    protected static string $resource = ClassImageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
