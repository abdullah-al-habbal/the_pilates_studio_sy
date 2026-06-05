<?php

namespace App\Filament\Admin\Resources\Packages\RelationManagers;

use App\Enums\BookingStatusEnum;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BookingsRelationManager extends RelationManager
{
    protected static string $relationship = 'bookings';

    protected static ?string $title = 'Bookings';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('Booking ID')
                    ->sortable(),

                TextColumn::make('user.fullname')
                    ->label('User')
                    ->searchable(),

                TextColumn::make('total_credits')
                    ->label('Credits')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('remaining_credits')
                    ->label('Remaining')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (BookingStatusEnum $state): string => $state->getColor())
                    ->sortable(),

                TextColumn::make('expires_at')
                    ->label('Expires')
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Purchased')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
