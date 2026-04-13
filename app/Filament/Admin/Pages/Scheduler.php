<?php

namespace App\Filament\Admin\Pages;

use App\Enums\AttendanceStatusEnum;
use App\Models\ClassSession;
use App\Repositories\Eloquent\ClassSession\ClassSessionEloquentRepository;
use App\Services\BookingSession\BookingSessionService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\App;

class Scheduler extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    public static function getNavigationLabel(): string
    {
        return __('dashboard.navigation.scheduler');
    }

    public function getHeading(): string
    {
        return __('dashboard.pages.scheduler.title');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('dashboard.navigation.groups.operations');
    }

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.admin.pages.scheduler';

    public function table(Table $table): Table
    {
        $repository = App::make(ClassSessionEloquentRepository::class);

        return $table
            ->query($repository->getSchedulerQuery())
            ->columns([
                TextColumn::make('date')
                    ->date('l, M j, Y')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('class.title')
                    ->label(__('dashboard.pages.scheduler.class'))
                    ->formatStateUsing(fn($state) => $state[app()->getLocale()] ?? $state['en'] ?? '')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('class.instructor.fullname')
                    ->label(__('dashboard.pages.scheduler.instructor'))
                    ->placeholder(__('dashboard.pages.scheduler.no_instructor'))
                    ->sortable(),
                TextColumn::make('time_range')
                    ->label(__('dashboard.pages.scheduler.time'))
                    ->state(fn(ClassSession $record) => substr($record->start_time, 0, 5) . ' - ' . substr($record->end_time, 0, 5)),
                TextColumn::make('attendance_summary')
                    ->label(__('dashboard.pages.scheduler.attendance_summary'))
                    ->state(
                        fn(ClassSession $record) => $record->bookingSessions()->where('attendance_status', AttendanceStatusEnum::ATTENDED)->count() . ' / ' .
                        $record->bookingSessions()->count()
                    )
                    ->badge()
                    ->color('info'),
                TextColumn::make('status')
                    ->badge(),
            ])
            ->actions([
                Action::make('attendance')
                    ->label(__('dashboard.pages.scheduler.attendance'))
                    ->icon('heroicon-o-user-check')
                    ->color('success')
                    ->modalHeading(function (ClassSession $record) {
                        $title = $record->class?->title[app()->getLocale()] ?? $record->class?->title['en'] ?? '';

                        return __('dashboard.pages.scheduler.modal.heading', [
                            'class' => $title,
                            'date' => $record->date->format('M j'),
                        ]);
                    })
                    ->modalContent(fn(ClassSession $record) => view('filament.admin.pages.scheduler.attendance-modal', ['session' => $record]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel(__('dashboard.pages.scheduler.modal.close')),
            ])
            ->groups([
                'date',
            ])
            ->defaultGroup('date')
            ->recordAction('attendance');
    }

    public function toggleAttendance(int $bookingSessionId, string $status): void
    {
        $service = App::make(BookingSessionService::class);
        $service->toggleAttendance($bookingSessionId, AttendanceStatusEnum::from($status));

        Notification::make()
            ->title(__('dashboard.pages.scheduler.notifications.attendance_updated'))
            ->success()
            ->send();
    }

    public function addWalkIn(int $sessionId, int $userId): void
    {
        $service = App::make(BookingSessionService::class);
        $service->oneTimeAttend($userId, $sessionId);

        Notification::make()
            ->title(__('dashboard.pages.scheduler.notifications.walkin_added'))
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label(__('dashboard.pages.scheduler.actions.refresh'))
                ->icon('heroicon-o-arrow-path')
                ->action(fn() => $this->redirect(static::getUrl())),
        ];
    }
}
