<?php

namespace App\Filament\Admin\Resources\Classes\Pages;

use App\Actions\ApplyCapacityToFutureSessionsAction;
use App\Enums\ClassSessionStatusEnum;
use App\Enums\ClassStatusEnum;
use App\Filament\Admin\Resources\Classes\ClassesResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
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
        $record = $this->getRecord();

        $futureSessionsCount = $record->sessions()
            ->where('date', '>=', now()->toDateString())
            ->where('status', ClassSessionStatusEnum::SCHEDULED)
            ->count();

        return [
            LocaleSwitcher::make(),

            Action::make('mark_cancelled')
                ->label('Cancel Class')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Cancel This Class')
                ->modalDescription('This will cancel the class. Members with active bookings should be notified.')
                ->modalSubmitActionLabel('Yes, cancel it')
                ->visible(fn () => $this->getRecord()->status === ClassStatusEnum::ACTIVE)
                ->action(function (): void {
                    $this->getRecord()->update(['status' => ClassStatusEnum::INACTIVE->value]);
                    $this->refreshFormData(['status']);
                    Notification::make()
                        ->title('Class has been cancelled.')
                        ->warning()
                        ->send();
                }),

            Action::make('apply_capacity_to_future_sessions')
                ->label('Apply Capacity to Future Sessions')
                ->icon('heroicon-o-arrow-path-rounded-square')
                ->color('warning')
                ->visible(fn () => $futureSessionsCount > 0)
                ->form([
                    TextInput::make('capacity')
                        ->label('New Capacity')
                        ->numeric()
                        ->required()
                        ->minValue(1)
                        ->integer()
                        ->default(fn () => $record->total_spots)
                        ->columnSpanFull(),

                    Placeholder::make('affected_count')
                        ->label('Affected Future Sessions')
                        ->content(fn () => (string) $futureSessionsCount.' session(s)')
                        ->columnSpanFull(),

                    Textarea::make('reason')
                        ->label('Reason')
                        ->required()
                        ->rows(3)
                        ->maxLength(500)
                        ->placeholder('Why is this capacity change being applied to future sessions?')
                        ->columnSpanFull(),
                ])
                ->modalHeading('Apply Capacity to Future Sessions')
                ->modalDescription(fn () => "Update class capacity and apply to all {$futureSessionsCount} future scheduled session(s).")
                ->modalSubmitActionLabel('Apply to Future Sessions')
                ->action(function (array $data) use ($record): void {
                    $result = app(ApplyCapacityToFutureSessionsAction::class)->execute(
                        class: $record,
                        capacity: (int) $data['capacity'],
                        reason: $data['reason'],
                    );

                    Notification::make()
                        ->title("Capacity applied to {$result['affected']} future session(s).")
                        ->success()
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
