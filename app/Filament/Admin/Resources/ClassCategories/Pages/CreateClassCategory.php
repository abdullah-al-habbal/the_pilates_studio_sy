<?php

namespace App\Filament\Admin\Resources\ClassCategories\Pages;

use App\Filament\Admin\Resources\ClassCategories\ClassCategoryResource;
use Filament\Resources\Pages\CreateRecord;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\CreateRecord\Concerns\Translatable;

class CreateClassCategory extends CreateRecord
{
    use Translatable;

    protected static string $resource = ClassCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            LocaleSwitcher::make(),
        ];
    }
}
