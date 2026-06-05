<?php

namespace App\Filament\Admin\Resources\StaticPages\Pages;

use App\Filament\Admin\Resources\StaticPages\StaticPageResource;
use Filament\Resources\Pages\EditRecord;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\EditRecord\Concerns\Translatable;

class EditStaticPage extends EditRecord
{
    use Translatable;

    protected static string $resource = StaticPageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            LocaleSwitcher::make(),
        ];
    }
}
