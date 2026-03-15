<?php

namespace App\Filament\Admin\Resources\Bookings\Tables;

use App\Enums\BookingStatusEnum;
use App\Models\User;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class BookingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label(__('dashboard.resources.bookings.fields.id'))
                    ->sortable()
                    ->searchable(),

                TextColumn::make('user.fullname')
                    ->label(__('dashboard.resources.bookings.fields.user'))
                    ->searchable(query: function ($query, $search) {
                        return $query->whereHas('user', function ($q) use ($search) {
                            $q->where('fullname', 'like', "%{$search}%");
                        });
                    })
                    ->sortable(query: function ($query, $direction) {
                        return $query->orderBy(
                            User::select('fullname')
                                ->whereColumn('users.id', 'bookings.user_id')
                                ->limit(1),
                            $direction
                        );
                    }),
                TextColumn::make('booking_sessions_count')
                    ->label(__('dashboard.resources.booking_sessions.plural'))
                    ->badge()
                    ->color('info')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('package.name')
                    ->label(__('dashboard.resources.bookings.fields.package'))
                    ->badge()
                    ->color('info')
                    ->searchable(),

                TextColumn::make('status')
                    ->label(__('dashboard.resources.bookings.fields.status'))
                    ->badge()
                    ->color(fn (BookingStatusEnum $state): string => $state->getColor())
                    ->icon(fn (BookingStatusEnum $state): ?string => $state->getIcon())
                    ->formatStateUsing(fn (BookingStatusEnum $state): string => $state->getLabel())
                    ->sortable(),

                TextColumn::make('total_credits')
                    ->label(__('dashboard.resources.bookings.fields.total_credits'))
                    ->numeric()
                    ->sortable(),

                TextColumn::make('remaining_credits')
                    ->label(__('dashboard.resources.bookings.fields.remaining_credits'))
                    ->numeric()
                    ->sortable()
                    ->color(fn ($state): string => $state > 0 ? 'success' : 'danger'),

                TextColumn::make('expires_at')
                    ->label(__('dashboard.resources.bookings.fields.expires_at'))
                    ->dateTime()
                    ->sortable()
                    ->color(fn ($state): string => $state && $state->isPast() ? 'danger' : 'success')
                    ->placeholder('—'),

                TextColumn::make('created_at')
                    ->label(__('dashboard.resources.bookings.fields.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label(__('dashboard.resources.bookings.fields.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('deleted_at')
                    ->label(__('dashboard.resources.bookings.fields.deleted_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
                SelectFilter::make('status')
                    ->label(__('dashboard.resources.bookings.fields.status'))
                    ->options(BookingStatusEnum::options()),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading(__('dashboard.resources.bookings.empty_state.heading'))
            ->emptyStateDescription(__('dashboard.resources.bookings.empty_state.description'))
            ->emptyStateIcon('heroicon-o-credit-card')
            ->defaultSort('created_at', 'desc');
    }
}
