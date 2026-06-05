<?php

namespace App\Filament\Admin\Resources\RecurrencePatterns\Pages;

use App\Filament\Admin\Resources\RecurrencePatterns\RecurrencePatternResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\EditRecord\Concerns\Translatable;

class EditRecurrencePattern extends EditRecord
{
    use Translatable;

    protected static string $resource = RecurrencePatternResource::class;

    protected function getHeaderActions(): array
    {
        return [
            LocaleSwitcher::make(),
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
