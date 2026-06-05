<?php

namespace App\Filament\Admin\Resources\Instructors\Pages;

use App\Filament\Admin\Resources\Instructors\InstructorResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\ViewRecord\Concerns\Translatable;

class ViewInstructor extends ViewRecord
{
    use Translatable;

    protected static string $resource = InstructorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            LocaleSwitcher::make(),
            EditAction::make(),
        ];
    }
}
