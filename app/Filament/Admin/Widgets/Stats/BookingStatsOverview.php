<?php

namespace App\Filament\Admin\Widgets\Stats;

use App\Enums\AttendanceStatusEnum;
use App\Enums\BookingSessionStatusEnum;
use App\Enums\BookingStatusEnum;
use App\Models\Booking;
use App\Models\BookingSession;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class BookingStatsOverview extends BaseWidget
{
    protected static ?int $sort = 2;

    protected ?string $pollingInterval = null;

    protected function getHeading(): ?string
    {
        return __('widgets.bookings.heading');
    }

    protected function getStats(): array
    {
        return [
            $this->totalBookingsStat(),
            $this->activeBookingsStat(),
            $this->exhaustedStat(),
            $this->expiredStat(),
            $this->cancelledBookingsStat(),
            $this->creditsCirculationStat(),
            $this->totalReservationsStat(),
            $this->reservedStat(),
            $this->attendedStat(),
            $this->cancelledSessionsStat(),
            $this->missedStat(),
        ];
    }

    private function bookingData(): array
    {
        return Cache::remember('widget.bookings.stats', now()->addMinutes(10), function () {
            return [
                'total' => Booking::withTrashed()->count(),
                BookingStatusEnum::ACTIVE->value => Booking::where('status', BookingStatusEnum::ACTIVE->value)->count(),
                BookingStatusEnum::EXHAUSTED->value => Booking::where('status', BookingStatusEnum::EXHAUSTED->value)->count(),
                BookingStatusEnum::EXPIRED->value => Booking::where('status', BookingStatusEnum::EXPIRED->value)->count(),
                BookingStatusEnum::CANCELLED->value => Booking::where('status', BookingStatusEnum::CANCELLED->value)->count(),
                'credits' => Booking::where('status', BookingStatusEnum::ACTIVE->value)->sum('remaining_credits'),
            ];
        });
    }

    private function sessionData(): array
    {
        return Cache::remember('widget.booking_sessions.stats', now()->addMinutes(10), function () {
            return [
                'total' => BookingSession::count(),
                BookingSessionStatusEnum::RESERVED->value => BookingSession::where('status', BookingSessionStatusEnum::RESERVED->value)->count(),
                'attended' => BookingSession::where('attendance_status', AttendanceStatusEnum::ATTENDED->value)->count(),
                BookingSessionStatusEnum::CANCELLED->value => BookingSession::where('status', BookingSessionStatusEnum::CANCELLED->value)->count(),
                'missed' => BookingSession::where('attendance_status', AttendanceStatusEnum::MISSED->value)->count(),
            ];
        });
    }

    private function totalBookingsStat(): Stat
    {
        return Stat::make(__('widgets.bookings.total'), $this->bookingData()['total'])
            ->descriptionIcon('heroicon-m-credit-card')
            ->color('gray');
    }

    private function activeBookingsStat(): Stat
    {
        return Stat::make(__('widgets.bookings.active'), $this->bookingData()[BookingStatusEnum::ACTIVE->value])
            ->description(__('widgets.bookings.active_desc'))
            ->descriptionIcon('heroicon-m-check-circle')
            ->color('success');
    }

    private function exhaustedStat(): Stat
    {
        return Stat::make(__('widgets.bookings.exhausted'), $this->bookingData()[BookingStatusEnum::EXHAUSTED->value])
            ->description(__('widgets.bookings.exhausted_desc'))
            ->descriptionIcon('heroicon-m-battery-0')
            ->color('warning');
    }

    private function expiredStat(): Stat
    {
        return Stat::make(__('widgets.bookings.expired'), $this->bookingData()[BookingStatusEnum::EXPIRED->value])
            ->description(__('widgets.bookings.expired_desc'))
            ->descriptionIcon('heroicon-m-clock')
            ->color('danger');
    }

    private function cancelledBookingsStat(): Stat
    {
        return Stat::make(__('widgets.bookings.cancelled'), $this->bookingData()[BookingStatusEnum::CANCELLED->value])
            ->descriptionIcon('heroicon-m-x-circle')
            ->color('danger');
    }

    private function creditsCirculationStat(): Stat
    {
        return Stat::make(__('widgets.bookings.credits'), $this->bookingData()['credits'])
            ->description(__('widgets.bookings.credits_desc'))
            ->descriptionIcon('heroicon-m-banknotes')
            ->color('info');
    }

    private function totalReservationsStat(): Stat
    {
        return Stat::make(__('widgets.bookings.total_reservations'), $this->sessionData()['total'])
            ->descriptionIcon('heroicon-m-ticket')
            ->color('gray');
    }

    private function reservedStat(): Stat
    {
        return Stat::make(__('widgets.bookings.reserved'), $this->sessionData()[BookingSessionStatusEnum::RESERVED->value])
            ->description(__('widgets.bookings.reserved_desc'))
            ->descriptionIcon('heroicon-m-calendar-days')
            ->color('info');
    }

    private function attendedStat(): Stat
    {
        return Stat::make(__('widgets.bookings.attended'), $this->sessionData()['attended'])
            ->description(__('widgets.bookings.attended_desc'))
            ->descriptionIcon('heroicon-m-check-badge')
            ->color('success');
    }

    private function cancelledSessionsStat(): Stat
    {
        return Stat::make(__('widgets.bookings.cancelled_sessions'), $this->sessionData()[BookingSessionStatusEnum::CANCELLED->value])
            ->descriptionIcon('heroicon-m-x-mark')
            ->color('warning');
    }

    private function missedStat(): Stat
    {
        return Stat::make(__('widgets.bookings.missed'), $this->sessionData()['missed'])
            ->description(__('widgets.bookings.missed_desc'))
            ->descriptionIcon('heroicon-m-user-minus')
            ->color('danger');
    }
}
