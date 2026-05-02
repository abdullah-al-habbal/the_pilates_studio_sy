<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Instructor;
use App\Services\Dashboard\StatsService;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class TopInstructorsWidget extends BaseWidget
{
    protected ?string $pollingInterval = '30s';

    public function __construct(private readonly StatsService $statsService)
    {
        parent::__construct();
    }

    protected function getHeading(): ?string
    {
        return __('dashboard.widgets.top_instructors.heading');
    }

    protected function getTableHeading(): string
    {
        return __('dashboard.widgets.top_instructors.table_heading');
    }

    public function table(Table $table): Table
    {
        $instructors = $this->statsService->getTopInstructors(5);

        return $table
            ->query(function () use ($instructors) {
                if ($instructors->isEmpty()) {
                    return Instructor::query()->whereRaw('1=0');
                }

                return Instructor::query()->whereIn('id', $instructors->pluck('id'));
            })
            ->columns([
                TextColumn::make('name')
                    ->label(__('dashboard.widgets.top_instructors.instructor')),
                TextColumn::make('attended_count')
                    ->label(__('dashboard.widgets.top_instructors.attended_sessions'))
                    ->state(fn($record) => $instructors->firstWhere('id', $record->id)?->attended_count ?? 0)
                    ->numeric()
                    ->sortable(),
            ]);
    }
}
