<?php

namespace App\Filament\Admin\Resources\Merchandise\MerchandiseCategories\Pages;

use App\Filament\Admin\Resources\Merchandise\MerchandiseCategories\MerchandiseCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use LaraZeus\SpatieTranslatable\Resources\Pages\ManageRecords\Concerns\Translatable;

class ManageMerchandiseCategories extends ManageRecords
{
    use Translatable;

    protected static string $resource = MerchandiseCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
