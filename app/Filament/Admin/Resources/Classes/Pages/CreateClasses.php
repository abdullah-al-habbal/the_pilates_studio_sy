<?php
// filePath: app/Filament/Admin/Resources/Classes/Pages/CreateClasses.php

namespace App\Filament\Admin\Resources\Classes\Pages;

use App\Filament\Admin\Resources\Classes\ClassesResource;
use Filament\Resources\Pages\CreateRecord;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\CreateRecord\Concerns\Translatable;

class CreateClasses extends CreateRecord
{
    use Translatable;

    protected static string $resource = ClassesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            LocaleSwitcher::make(),
        ];
    }
}
