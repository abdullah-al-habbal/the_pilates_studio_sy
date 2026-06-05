<?php

// Pages/CreateCenterMerchandise.php

namespace App\Filament\Admin\Resources\Merchandise\CenterMerchandises\Pages;

use App\Filament\Admin\Resources\Merchandise\CenterMerchandises\CenterMerchandiseResource;
use Filament\Resources\Pages\CreateRecord;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\CreateRecord\Concerns\Translatable;

class CreateCenterMerchandise extends CreateRecord
{
    use Translatable;

    protected static string $resource = CenterMerchandiseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            LocaleSwitcher::make(),
        ];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (isset($data['images'])) {
            $primaryFound = false;
            foreach ($data['images'] as &$image) {
                if ($image['is_primary'] && ! $primaryFound) {
                    $primaryFound = true;
                } else {
                    $image['is_primary'] = false;
                }
            }
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
