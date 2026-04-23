<?php

namespace App\Services\Dashboard;

use App\Services\Booking\BookingService;
use App\Services\BookingSession\BookingSessionService;
use App\Services\ClassCategory\ClassCategoryService;
use App\Services\ClassSession\ClassSessionService;
use App\Services\Instructor\InstructorService;
use App\Services\User\UserService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class StatsService
{
    public function __construct(
        private readonly BookingService $bookingService,
        private readonly BookingSessionService $bookingSessionService,
        private readonly ClassCategoryService $classCategoryService,
        private readonly ClassSessionService $classSessionService,
        private readonly InstructorService $instructorService,
        private readonly UserService $userService,
    ) {
    }

    public function getOverviewStats(): array
    {
        return Cache::remember('dashboard.overview.stats', now()->addHour(), function () {
            $totalActiveUsers = $this->userService->countActiveUsers();
            $activeBookings = $this->bookingService->countActive();
            $totalCreditsSold = $this->bookingService->sumTotalCredits();
            $creditsConsumed = $this->bookingService->sumUsedCredits();
            $attendanceRate = $this->calculateAttendanceRate();
            $missedCurrent = $this->bookingSessionService->countMissedForMonth(now()->month);
            $missedPrevious = $this->bookingSessionService->countMissedForMonth(now()->subMonth()->month);
            $missedTrend = $missedPrevious > 0 ? (int) round((($missedCurrent - $missedPrevious) / $missedPrevious) * 100) : 0;
            $cancellationRate = $this->calculateCancellationRate();
            $fillRate = $this->classSessionService->getFillRate();
            $upcomingFullSessions = $this->classSessionService->countUpcomingFullSessions();
            $revenueByPackage = $this->bookingService->getRevenueByPackage();

            return [
                'total_active_users' => $totalActiveUsers,
                'active_bookings' => $activeBookings,
                'total_credits_sold' => $totalCreditsSold,
                'credits_consumed' => $creditsConsumed,
                'attendance_rate' => $attendanceRate,
                'attendance_trend' => $this->bookingSessionService->getAttendanceTrend(30)->values()->toArray(),
                'missed' => $missedCurrent,
                'missed_trend' => $missedTrend,
                'cancellation_rate' => $cancellationRate,
                'fill_rate' => $fillRate,
                'upcoming_full_sessions' => $upcomingFullSessions,
                'revenue_by_package' => $revenueByPackage,
            ];
        });
    }

    private function calculateAttendanceRate(): int
    {
        $total = $this->bookingSessionService->totalSessionsCount();
        $attended = $this->bookingSessionService->countAttended();

        return $total > 0 ? (int) round(($attended / $total) * 100) : 0;
    }

    private function calculateCancellationRate(): int
    {
        $total = $this->bookingSessionService->totalSessionsCount();
        $cancelled = $this->bookingSessionService->countCancelled();

        return $total > 0 ? (int) round(($cancelled / $total) * 100) : 0;
    }

    public function getTopInstructors(int $limit = 5): Collection
    {
        return Cache::remember("dashboard.top_instructors.{$limit}", now()->addMinutes(15), function () use ($limit) {
            return $this->instructorService->getTopInstructors($limit);
        });
    }

    public function getTopCategories(int $limit = 3): Collection
    {
        return Cache::remember("dashboard.top_categories.{$limit}", now()->addMinutes(30), function () use ($limit) {
            return $this->classCategoryService->getTopCategories($limit);
        });
    }

    public function getAttendanceTrend(int $days = 30): Collection
    {
        return Cache::remember("dashboard.attendance_trend.{$days}", now()->addMinutes(15), function () use ($days) {
            return $this->bookingSessionService->getAttendanceTrend($days);
        });
    }
}
