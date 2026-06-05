<?php

namespace App\Filament\Admin\Resources\RecurrencePatterns\Pages;

use App\Filament\Admin\Resources\RecurrencePatterns\RecurrencePatternResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\ViewRecord\Concerns\Translatable;

class ViewRecurrencePattern extends ViewRecord
{
    use Translatable;

    protected static string $resource = RecurrencePatternResource::class;

    protected function getHeaderActions(): array
    {
        return [
            LocaleSwitcher::make(),
            EditAction::make(),
        ];
    }
}
