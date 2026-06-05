<?php

namespace App\Filament\Admin\Resources\ClassCategories\Pages;

use App\Filament\Admin\Resources\ClassCategories\ClassCategoryResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\ViewRecord\Concerns\Translatable;

class ViewClassCategory extends ViewRecord
{
    use Translatable;

    protected static string $resource = ClassCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            LocaleSwitcher::make(),
            EditAction::make(),
        ];
    }
}
