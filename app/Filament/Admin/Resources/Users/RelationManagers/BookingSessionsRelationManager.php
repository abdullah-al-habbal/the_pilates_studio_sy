<?php

namespace App\Filament\Admin\Resources\Users\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BookingSessionsRelationManager extends RelationManager
{
    protected static string $relationship = 'bookingSessions';
    protected static ?string $title = 'Class Sessions';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('classSchedule.class_name')
                    ->label('Class'),
                TextColumn::make('session_date')
                    ->dateTime(),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('attendance_status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'attended' => 'success',
                        'missed'   => 'danger',
                        default    => 'secondary',
                    }),
            ]);
    }
}