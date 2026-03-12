<?php

namespace App\Filament\Admin\Resources\ClassImages\Pages;

use App\Filament\Admin\Resources\ClassImages\ClassImageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListClassImages extends ListRecords
{
    protected static string $resource = ClassImageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
