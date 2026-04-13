<?php

namespace App\Filament\Admin\Pages;

use App\Enums\AttendanceStatusEnum;
use App\Models\ClassSession;
use App\Services\BookingSession\BookingSessionService;
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

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.admin.pages.scheduler';

    public function table(Table $table): Table
    {
        return $table
            ->query(ClassSession::query()->latest('date'))
            ->columns([
                TextColumn::make('date')
                    ->date('l, M j, Y')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('class.title')
                    ->label('Class')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('class.instructor.fullname')
                    ->label('Instructor')
                    ->placeholder('No Instructor')
                    ->sortable(),
                TextColumn::make('time_range')
                    ->label('Time')
                    ->state(fn(ClassSession $record) => substr($record->start_time, 0, 5) . ' - ' . substr($record->end_time, 0, 5)),
                TextColumn::make('attendance_summary')
                    ->label('Attendance')
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
                TableAction::make('attendance')
                    ->label('Manage Attendance')
                    ->icon('heroicon-o-user-check')
                    ->color('success')
                    ->modalHeading(fn(ClassSession $record) => 'Attendance: ' . ($record->class?->title['en'] ?? 'Class') . ' (' . $record->date->format('M j') . ')')
                    ->modalContent(fn(ClassSession $record) => view('filament.admin.pages.scheduler.attendance-modal', ['session' => $record]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),
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
            ->title('Attendance updated')
            ->success()
            ->send();
    }

    public function addWalkIn(int $sessionId, int $userId): void
    {
        $service = App::make(BookingSessionService::class);
        $service->oneTimeAttend($userId, $sessionId);

        Notification::make()
            ->title('Walk-in attendee added')
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Refresh Data')
                ->icon('heroicon-o-arrow-path')
                ->action(fn() => $this->redirect(static::getUrl())),
        ];
    }
}
