<?php

namespace App\Filament\Admin\Pages;

use App\Models\Booking;
use App\Models\Classes;
use App\Models\MerchandiseOrder;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class Reports extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-bar';

    protected static ?string $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.admin.pages.reports';

    public function getViewData(): array
    {
        return [
            'stats' => $this->getStats(),
            'popularClasses' => $this->getPopularClasses(),
            'merchandiseSales' => $this->getMerchandiseSales(),
        ];
    }

    protected function getStats(): array
    {
        $bookingRevenue = Booking::join('packages', 'bookings.package_id', '=', 'packages.id')
            ->sum('packages.price');

        $merchandiseRevenue = MerchandiseOrder::join('center_merchandises', 'merchandise_orders.merchandise_id', '=', 'center_merchandises.id')
            ->sum(DB::raw('center_merchandises.price * merchandise_orders.quantity'));

        return [
            'booking_revenue' => $bookingRevenue,
            'merchandise_revenue' => $merchandiseRevenue,
            'total_revenue' => $bookingRevenue + $merchandiseRevenue,
            'total_bookings' => Booking::count(),
            'total_merchandise_orders' => MerchandiseOrder::count(),
        ];
    }

    protected function getPopularClasses(): \Illuminate\Support\Collection
    {
        return Classes::withCount('sessions')
            ->get()
            ->map(function ($class) {
                $totalSessions = $class->sessions->count();
                $totalAttendance = $class->sessions->sum(function ($session) {
                    return $session->bookingSessions()->count();
                });

                return [
                    'title' => $class->title['en'] ?? 'Class',
                    'sessions_count' => $totalSessions,
                    'total_attendance' => $totalAttendance,
                    'avg_attendance' => $totalSessions > 0 ? round($totalAttendance / $totalSessions, 1) : 0,
                ];
            })
            ->sortByDesc('total_attendance')
            ->take(5);
    }

    protected function getMerchandiseSales(): \Illuminate\Support\Collection
    {
        return MerchandiseOrder::with('merchandise')
            ->select('merchandise_id', DB::raw('SUM(quantity) as total_quantity'))
            ->groupBy('merchandise_id')
            ->get()
            ->map(function ($order) {
                return [
                    'name' => $order->merchandise?->name['en'] ?? 'Product',
                    'quantity' => $order->total_quantity,
                    'revenue' => $order->total_quantity * ($order->merchandise?->price ?? 0),
                ];
            })
            ->sortByDesc('revenue')
            ->take(5);
    }
}
