<?php

namespace App\Livewire;

use App\Enums\AttendanceStatusEnum;
use App\Models\ClassSession;
use App\Services\BookingSession\BookingSessionService;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;
use Livewire\Component;

class AttendanceModalContent extends Component implements HasActions
{
    use InteractsWithActions;

    public ClassSession $session;

    /** @var Collection<int, \App\Models\BookingSession> */
    public Collection $bookings;

    /** @var Collection<int, \App\Models\User> */
    public Collection $allUsers;

    public bool $isFull;

    public string $walkInMode = 'existing';

    public ?int $userId = null;

    public array $newUser = [
        'fullname' => '',
        'phone_number' => '',
        'email' => '',
        'password' => '',
    ];

    public function mount(ClassSession $session, Collection $bookings, Collection $allUsers, bool $isFull): void
    {
        $this->session = $session;
        $this->bookings = $bookings;
        $this->allUsers = $allUsers;
        $this->isFull = $isFull;
    }

    private function refreshBookings(): void
    {
        $this->bookings = $this->session->bookingSessions()
            ->with([
                'booking.user.bookings' => fn ($q) => $q->where('status', 'active')->where('remaining_credits', '>', 0),
            ])
            ->get();
    }

    public function markAttendedAction(int $bookingSessionId): Action
    {
        return Action::make('mark_attended_'.$bookingSessionId)
            ->label('✓ '.__('dashboard.pages.scheduler.modal.attended'))
            ->color('success')
            ->action(function () use ($bookingSessionId) {
                app(BookingSessionService::class)
                    ->toggleAttendance($bookingSessionId, AttendanceStatusEnum::ATTENDED);
                $this->refreshBookings();
                Notification::make()
                    ->title(__('dashboard.pages.scheduler.notifications.attendance_updated'))
                    ->success()
                    ->send();
            });
    }

    public function markMissedAction(int $bookingSessionId): Action
    {
        return Action::make('mark_missed_'.$bookingSessionId)
            ->label('✗ '.__('dashboard.pages.scheduler.modal.missed'))
            ->color('danger')
            ->action(function () use ($bookingSessionId) {
                app(BookingSessionService::class)
                    ->toggleAttendance($bookingSessionId, AttendanceStatusEnum::MISSED);
                $this->refreshBookings();
                Notification::make()
                    ->title(__('dashboard.pages.scheduler.notifications.attendance_updated'))
                    ->success()
                    ->send();
            });
    }

    public function addWalkInAction(): Action
    {
        return Action::make('add_walkin')
            ->label(__('dashboard.pages.scheduler.modal.attend_now'))
            ->icon('heroicon-o-user-plus')
            ->form([
                Select::make('user_id')
                    ->label(__('dashboard.pages.scheduler.modal.select_member'))
                    ->options($this->allUsers->pluck('fullname', 'id'))
                    ->searchable()
                    ->visible(fn () => $this->walkInMode === 'existing'),
            ])
            ->action(function (array $data) {
                if (! empty($data['user_id'])) {
                    app(BookingSessionService::class)
                        ->oneTimeAttend((int) $data['user_id'], $this->session->id);
                }
                $this->refreshBookings();
                Notification::make()
                    ->title(__('dashboard.pages.scheduler.notifications.walkin_added'))
                    ->success()
                    ->send();
            });
    }

    public function createAndAttendAction(): Action
    {
        return Action::make('create_and_attend')
            ->label(__('dashboard.pages.scheduler.modal.attend_now'))
            ->icon('heroicon-o-user-plus')
            ->form([
                TextInput::make('fullname')
                    ->label(__('dashboard.resources.users.fields.fullname'))
                    ->required(),
                TextInput::make('phone_number')
                    ->label(__('dashboard.resources.users.fields.phone_number'))
                    ->required(),
                TextInput::make('email')
                    ->label(__('dashboard.resources.users.fields.email')),
                TextInput::make('password')
                    ->label(__('dashboard.resources.users.fields.password'))
                    ->default('pilates'),
            ])
            ->action(function (array $data) {
                $service = app(BookingSessionService::class);
                $user = $service->createWalkInUser($data);
                $service->oneTimeAttend($user->id, $this->session->id);
                $this->refreshBookings();
                Notification::make()
                    ->title(__('dashboard.pages.scheduler.notifications.walkin_added'))
                    ->success()
                    ->send();
            });
    }

    public function render()
    {
        return view('livewire.attendance-modal-content', [
            'attendedCount' => $this->bookings->where('attendance_status.value', 'attended')->count(),
        ]);
    }
}
