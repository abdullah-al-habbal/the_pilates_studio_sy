<?php

namespace App\Filament\Admin\Widgets;

use App\Services\Dashboard\StatsService;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class AttendanceTrendChart extends ChartWidget
{
    protected ?string $pollingInterval = '30s';

    public function __construct(private readonly StatsService $statsService)
    {
        parent::__construct();
    }

    public function getHeading(): ?string
    {
        return __('dashboard.widgets.attendance_trend.heading');
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $trend = $this->statsService->getAttendanceTrend(30);

        return [
            'datasets' => [
                [
                    'label' => __('dashboard.widgets.attendance_trend.attended_sessions'),
                    'data' => $trend->values()->toArray(),
                    'borderColor' => '#4CAF50',
                    'backgroundColor' => 'rgba(76, 175, 80, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
            'labels' => $trend->keys()->map(fn ($date) => Carbon::parse($date)->format('M d'))->toArray(),
        ];
    }
}
