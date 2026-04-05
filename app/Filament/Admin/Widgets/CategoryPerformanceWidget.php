<?php

namespace App\Filament\Admin\Widgets;

use App\Services\Dashboard\StatsService;
use Filament\Widgets\ChartWidget;

class CategoryPerformanceWidget extends ChartWidget
{
    protected ?string $pollingInterval = '30s';

    public function getHeading(): ?string
    {
        return __('dashboard.widgets.category_performance.heading');
    }

    protected function getType(): string
    {
        return 'pie';
    }

    protected function getData(): array
    {
        $categories = app(StatsService::class)->getTopCategories(5);

        return [
            'datasets' => [
                [
                    'label' => __('dashboard.widgets.category_performance.attended_sessions'),
                    'data' => $categories->pluck('attended_count')->toArray(),
                    'backgroundColor' => ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF'],
                ],
            ],
            'labels' => $categories->pluck('name')->toArray(),
        ];
    }
}
