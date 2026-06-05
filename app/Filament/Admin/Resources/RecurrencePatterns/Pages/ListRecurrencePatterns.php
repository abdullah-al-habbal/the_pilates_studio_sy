<?php

namespace App\Filament\Admin\Resources\RecurrencePatterns\Pages;

use App\Filament\Admin\Resources\RecurrencePatterns\RecurrencePatternResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use LaraZeus\SpatieTranslatable\Resources\Pages\ListRecords\Concerns\Translatable;

class ListRecurrencePatterns extends ListRecords
{
    use Translatable;

    protected static string $resource = RecurrencePatternResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
