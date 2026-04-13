<?php

namespace App\Filament\Admin\Resources\Merchandise\CenterMerchandises\Pages;

use App\Filament\Admin\Resources\Merchandise\CenterMerchandises\CenterMerchandiseResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCenterMerchandise extends CreateRecord
{
    protected static string $resource = CenterMerchandiseResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
