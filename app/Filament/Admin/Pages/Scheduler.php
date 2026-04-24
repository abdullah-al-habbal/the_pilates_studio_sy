<?php

// app\Filament\Admin\Pages\Scheduler.php
declare(strict_types=1);

namespace App\Filament\Admin\Pages;

use App\Enums\AttendanceStatusEnum;
use App\Models\ClassSession;
use App\Models\User;
use App\Repositories\Eloquent\ClassSession\ClassSessionEloquentRepository;
use App\Services\BookingSession\BookingSessionService;
use BackedEnum;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Livewire\Attributes\On;

class Scheduler extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.admin.pages.scheduler';

    public string $selectedDate;

    public array $availableDates = [];

    /** @var Collection<int, \App\Models\ClassSession> */
    public Collection $sessions;

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

    public static function getNavigationBadge(): ?string
    {
        return cache()->remember(
            'filament.scheduler.today_count',
            now()->addMinutes(5),
            fn () => (string) ClassSession::where('status', 'scheduled')
                ->whereDate('date', today())
                ->count()
        );
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        $count = static::getNavigationBadge();

        return ($count && $count > 0) ? 'primary' : 'gray';
    }

    public function getSubheading(): ?string
    {
        return Carbon::parse($this->selectedDate)->isoFormat('dddd, MMMM D, YYYY');
    }

    public function mount(): void
    {
        $this->selectedDate = today()->format('Y-m-d');
        $this->loadAvailableDates();
        $this->loadSessions();
    }

    private function loadAvailableDates(): void
    {
        $this->availableDates = ClassSession::where('status', 'scheduled')
            ->selectRaw('DATE(date) as session_date')
            ->distinct()
            ->pluck('session_date')
            ->map(fn ($d) => Carbon::parse($d)->format('Y-m-d'))
            ->values()
            ->toArray();
    }

    public function updatedSelectedDate(): void
    {
        $this->loadSessions();
    }

    public function goToToday(): void
    {
        $this->selectedDate = today()->format('Y-m-d');
        $this->loadSessions();
    }

    public function loadSessions(): void
    {
        $this->sessions = App::make(ClassSessionEloquentRepository::class)
            ->getSessionsByDate($this->selectedDate);
    }

    #[On('attendance-updated')]
    public function handleAttendanceUpdate(): void
    {
        $this->loadSessions();
    }

    public function toggleAttendance(int $bookingSessionId, string $status): void
    {
        App::make(BookingSessionService::class)
            ->toggleAttendance($bookingSessionId, AttendanceStatusEnum::from($status));

        $this->loadSessions();

        Notification::make()
            ->title(__('dashboard.pages.scheduler.notifications.attendance_updated'))
            ->success()
            ->send();
    }

    public function addWalkIn(int $sessionId, int $userId): void
    {
        App::make(BookingSessionService::class)
            ->oneTimeAttend($userId, $sessionId);

        $this->loadSessions();

        Notification::make()
            ->title(__('dashboard.pages.scheduler.notifications.walkin_added'))
            ->success()
            ->send();
    }

    public function createAndAttend(int $sessionId, array $userData): void
    {
        $service = App::make(BookingSessionService::class);
        $user = $service->createWalkInUser($userData);
        $service->oneTimeAttend($user->id, $sessionId);

        $this->loadSessions();

        Notification::make()
            ->title(__('dashboard.pages.scheduler.notifications.walkin_added'))
            ->success()
            ->send();
    }

    public function allUsers(): Collection
    {
        return User::orderBy('fullname')->get(['id', 'fullname', 'phone_number']);
    }

    public function getSessionActions(int $sessionId): array
    {
        $session = $this->sessions->firstWhere('id', $sessionId);
        if (! $session) {
            return [];
        }

        $bookings = $session->bookingSessions()->with([
            'booking.user.bookings' => fn ($q) => $q->where('status', 'active')->where('remaining_credits', '>', 0),
        ])->get();

        $allUsers = User::orderBy('fullname')->get(['id', 'fullname', 'phone_number']);
        $isFull = $session->total_spots > 0 && $session->bookingSessions->count() >= $session->total_spots;

        return [
            Action::make('attendance_'.$sessionId)
                ->label(__('dashboard.pages.scheduler.attendance'))
                ->icon('heroicon-o-clipboard-document-check')
                ->modalHeading(__('dashboard.pages.scheduler.modal.heading', [
                    'class' => $session->class?->title[app()->getLocale()] ?? '—',
                    'date' => $session->date->format('M j'),
                ]))
                ->modalContent(fn () => view('livewire.attendance-modal-wrapper', [
                    'sessionId' => $sessionId,
                ]))
                ->modalSubmitAction(false)
                ->modalCancelActionLabel(__('dashboard.pages.scheduler.modal.close'))
                ->extraModalWindowAttributes(['class' => 'fi-modal-window'])
                ->closeModalByClickingAway(false)
                ->modalWidth('2xl'),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('select_date')
                ->label('Jump to Date')
                ->icon('heroicon-o-calendar')
                ->form([
                    DatePicker::make('date')
                        ->default(now())
                        ->native(false),
                ])
                ->action(function (array $data) {
                    $this->selectedDate = $data['date'];
                    $this->loadSessions();
                }),
            Action::make('refresh')
                ->label(__('dashboard.pages.scheduler.actions.refresh'))
                ->icon('heroicon-o-arrow-path')
                ->action(fn () => $this->loadSessions()),
        ];
    }
}
