<?php

namespace App\Filament\Admin\Resources\ClassCategories\Pages;

use App\Filament\Admin\Resources\ClassCategories\ClassCategoryResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewClassCategory extends ViewRecord
{
    protected static string $resource = ClassCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
