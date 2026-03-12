<?php
// filePath: app/Filament/Admin/Resources/BookingSessions/Tables/BookingSessionsTable.php

namespace App\Filament\Admin\Resources\BookingSessions\Tables;

use App\Enums\BookingSessionStatusEnum;
use App\Models\Classes;
use App\Models\ClassSession;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BookingSessionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label(__('dashboard.resources.booking_sessions.fields.id'))
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('booking.id')
                    ->label(__('dashboard.resources.booking_sessions.fields.booking_id'))
                    ->sortable()
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('booking', fn($q) =>
                            $q->where('id', 'like', "%{$search}%")
                        );
                    })
                    ->toggleable(),

                TextColumn::make('booking.user.fullname')
                    ->label(__('dashboard.resources.booking_sessions.fields.user'))
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('booking.user', fn($q) =>
                            $q->where('fullname', 'like', "%{$search}%")
                        );
                    })
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy(
                            \App\Models\User::select('fullname')
                                ->join('bookings', 'users.id', '=', 'bookings.user_id')
                                ->whereColumn('bookings.id', 'booking_sessions.booking_id')
                                ->limit(1),
                            $direction
                        );
                    })
                    ->toggleable(),

                TextColumn::make('classSession.class.title')
                    ->label(__('dashboard.resources.booking_sessions.fields.class'))
                    ->state(function ($record) {
                        if (!$record->classSession || !$record->classSession->class) {
                            return '—';
                        }
                        return $record->classSession->class->getTranslation('title', app()->getLocale());
                    })
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        $locale = app()->getLocale();
                        return $query->whereHas('classSession.class', function ($q) use ($search, $locale) {
                            $q->whereRaw("JSON_EXTRACT(title, '$.{$locale}') LIKE ?", ["%{$search}%"]);
                        });
                    })
                    ->toggleable(),

                TextColumn::make('classSession.date')
                    ->label(__('dashboard.resources.booking_sessions.fields.date'))
                    ->date('M d, Y')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('classSession.start_time')
                    ->label(__('dashboard.resources.booking_sessions.fields.start_time'))
                    ->time('H:i')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('status')
                    ->label(__('dashboard.resources.booking_sessions.fields.status'))
                    ->badge()
                    ->color(fn (BookingSessionStatusEnum $state): string => $state->getColor())
                    ->icon(fn (BookingSessionStatusEnum $state): ?string => $state->getIcon())
                    ->formatStateUsing(fn (BookingSessionStatusEnum $state): string => $state->getLabel())
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('cancelled_at')
                    ->label(__('dashboard.resources.booking_sessions.fields.cancelled_at'))
                    ->dateTime('M d, Y H:i')
                    ->sortable()
                    ->placeholder('—')
                    ->color(fn ($state): string => $state ? 'danger' : 'success')
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label(__('dashboard.resources.booking_sessions.fields.created_at'))
                    ->dateTime('M d, Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label(__('dashboard.resources.booking_sessions.fields.updated_at'))
                    ->dateTime('M d, Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('dashboard.resources.booking_sessions.fields.status'))
                    ->options(BookingSessionStatusEnum::options())
                    ->multiple()
                    ->searchable()
                    ->preload(),

                SelectFilter::make('booking_id')
                    ->label(__('dashboard.resources.booking_sessions.fields.booking'))
                    ->relationship('booking', 'id')
                    ->searchable()
                    ->preload()
                    ->multiple()
                    ->optionsLimit(50),

                SelectFilter::make('class_session_id')
                    ->label(__('dashboard.resources.booking_sessions.fields.class_session'))
                    ->options(function () {
                        $locale = app()->getLocale();
                        return ClassSession::query()
                            ->with('class')
                            ->whereHas('class')
                            ->get()
                            ->mapWithKeys(function (ClassSession $session) use ($locale) {
                                $classTitle = $session->class?->getTranslation('title', $locale) ?? 'Unknown Class';
                                return [
                                    $session->id => $classTitle . ' - ' .
                                        $session->date->format('M d, Y') . ' ' .
                                        substr($session->start_time, 0, 5)
                                ];
                            })
                            ->toArray();
                    })
                    ->searchable()
                    ->multiple()
                    ->preload()
                    ->optionsLimit(50),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label(__('dashboard.actions.view')),
                EditAction::make()
                    ->label(__('dashboard.actions.edit')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label(__('dashboard.actions.delete_selected')),
                ]),
            ])
            ->emptyStateHeading(__('dashboard.resources.booking_sessions.empty_state.heading'))
            ->emptyStateDescription(__('dashboard.resources.booking_sessions.empty_state.description'))
            ->emptyStateIcon('heroicon-o-ticket')
            ->defaultSort('created_at', 'desc');
    }
}
