<?php

namespace App\Filament\Admin\Resources\ClassCategories\Pages;

use App\Filament\Admin\Resources\ClassCategories\ClassCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListClassCategories extends ListRecords
{
    protected static string $resource = ClassCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
