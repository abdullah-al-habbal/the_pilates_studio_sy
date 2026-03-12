<?php

namespace App\Filament\Admin\Resources\Classes\Pages;

use App\Filament\Admin\Resources\Classes\ClassesResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use LaraZeus\SpatieTranslatable\Resources\Pages\ListRecords\Concerns\Translatable;

class ListClasses extends ListRecords
{
    use Translatable;
    protected static string $resource = ClassesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
