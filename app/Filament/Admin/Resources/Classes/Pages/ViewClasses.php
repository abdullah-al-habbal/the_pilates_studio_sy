<?php

namespace App\Filament\Admin\Resources\Classes\Pages;

use App\Enums\ClassStatusEnum;
use App\Filament\Admin\Resources\Classes\ClassesResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;

use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\ViewRecord\Concerns\Translatable;

class ViewClasses extends ViewRecord
{
    use Translatable;

    protected static string $resource = ClassesResource::class;

    public function getTitle(): string|Htmlable
    {
        return $this->getRecord()->title;
    }

    public function getBreadcrumb(): string
    {
        return $this->getRecord()->title;
    }

    protected function getHeaderActions(): array
    {
        return [
            LocaleSwitcher::make(),
            Action::make('mark_completed')
                ->label('Mark Completed')
                ->icon('heroicon-s-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Mark Class as Completed')
                ->modalDescription('This will mark the class as completed. All active bookings will be updated.')
                ->modalSubmitActionLabel('Yes, complete it')
                ->visible(fn() => $this->getRecord()->status === ClassStatusEnum::ACTIVE)
                ->action(function (): void {
                    $this->getRecord()->update(['status' => ClassStatusEnum::ARCHIVED->value]);
                    $this->refreshFormData(['status']);
                    Notification::make()
                        ->title('Class marked as completed.')
                        ->success()
                        ->send();
                }),

            Action::make('mark_cancelled')
                ->label('Cancel Class')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Cancel This Class')
                ->modalDescription('This will cancel the class. Members with active bookings should be notified.')
                ->modalSubmitActionLabel('Yes, cancel it')
                ->visible(fn() => $this->getRecord()->status === ClassStatusEnum::ACTIVE)
                ->action(function (): void {
                    $this->getRecord()->update(['status' => ClassStatusEnum::INACTIVE->value]);
                    $this->refreshFormData(['status']);
                    Notification::make()
                        ->title('Class has been cancelled.')
                        ->warning()
                        ->send();
                }),

            EditAction::make(),

            DeleteAction::make()
                ->successNotificationTitle('Class deleted successfully.'),

            RestoreAction::make()
                ->successNotificationTitle('Class restored successfully.'),

            ForceDeleteAction::make()
                ->successNotificationTitle('Class permanently deleted.'),
        ];
    }
}
