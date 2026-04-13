<?php

namespace App\Filament\Admin\Resources\Merchandise\MerchandiseCategories\Pages;

use App\Filament\Admin\Resources\Merchandise\MerchandiseCategories\MerchandiseCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageMerchandiseCategories extends ManageRecords
{
    protected static string $resource = MerchandiseCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
