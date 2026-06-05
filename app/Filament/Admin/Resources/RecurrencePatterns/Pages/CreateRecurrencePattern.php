<?php

namespace App\Filament\Admin\Resources\RecurrencePatterns\Pages;

use App\Filament\Admin\Resources\RecurrencePatterns\RecurrencePatternResource;
use Filament\Resources\Pages\CreateRecord;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\CreateRecord\Concerns\Translatable;

class CreateRecurrencePattern extends CreateRecord
{
    use Translatable;

    protected static string $resource = RecurrencePatternResource::class;

    protected function getHeaderActions(): array
    {
        return [
            LocaleSwitcher::make(),
        ];
    }
}
