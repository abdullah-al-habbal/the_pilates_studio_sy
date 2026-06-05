<?php

// Pages/EditCenterMerchandise.php

namespace App\Filament\Admin\Resources\Merchandise\CenterMerchandises\Pages;

use App\Filament\Admin\Resources\Merchandise\CenterMerchandises\CenterMerchandiseResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\EditRecord\Concerns\Translatable;

class EditCenterMerchandise extends EditRecord
{
    use Translatable;

    protected static string $resource = CenterMerchandiseResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (isset($data['images'])) {
            $primaryFound = false;
            foreach ($data['images'] as &$image) {
                if (($image['is_primary'] ?? false) && ! $primaryFound) {
                    $primaryFound = true;
                } else {
                    $image['is_primary'] = false;
                }
            }
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            LocaleSwitcher::make(),
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
