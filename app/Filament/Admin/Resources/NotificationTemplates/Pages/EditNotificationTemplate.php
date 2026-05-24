<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\NotificationTemplates\Pages;

use App\Filament\Admin\Resources\NotificationTemplates\NotificationTemplateResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditNotificationTemplate extends EditRecord
{
    protected static string $resource = NotificationTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return [
            'key' => $data['key'],
            'title' => [
                'en' => $data['title_en'] ?? null,
                'ar' => $data['title_ar'] ?? null,
            ],
            'body' => [
                'en' => $data['body_en'] ?? null,
                'ar' => $data['body_ar'] ?? null,
            ],
            'data' => $data['data'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ];
    }
}
