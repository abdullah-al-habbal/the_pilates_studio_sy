<?php

namespace App\Filament\Admin\Resources\BookingSessions\Schemas;

use App\Enums\BookingSessionStatusEnum;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\IconPosition;

class BookingSessionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('dashboard.resources.booking_sessions.sections.information'))
                    ->description(__('dashboard.resources.booking_sessions.sections.information_desc'))
                    ->icon('heroicon-o-ticket')
                    ->collapsible()
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('id')
                                ->label(__('dashboard.resources.booking_sessions.fields.id'))
                                ->weight(FontWeight::Bold)
                                ->color('primary')
                                ->copyable()
                                ->copyMessage(__('dashboard.messages.copied'))
                                ->copyMessageDuration(1500)
                                ->icon('heroicon-o-clipboard')
                                ->iconPosition(IconPosition::After),

                            TextEntry::make('status')
                                ->label(__('dashboard.resources.booking_sessions.fields.status'))
                                ->badge()
                                ->color(fn (BookingSessionStatusEnum $state): string => $state->getColor())
                                ->icon(fn (BookingSessionStatusEnum $state): ?string => $state->getIcon())
                                ->formatStateUsing(fn (BookingSessionStatusEnum $state): string => $state->getLabel()),
                        ]),

                        Grid::make(2)->schema([
                            Section::make(__('dashboard.resources.booking_sessions.sections.booking_details'))
                                ->icon('heroicon-o-credit-card')
                                ->schema([
                                    TextEntry::make('booking.id')
                                        ->label(__('dashboard.resources.booking_sessions.fields.booking_id'))
                                        ->url(fn ($record) => $record->booking ? route('filament.admin.resources.bookings.view', $record->booking) : null)
                                        ->color('primary')
                                        ->icon('heroicon-o-link'),

                                    TextEntry::make('booking.user.fullname')
                                        ->label(__('dashboard.resources.booking_sessions.fields.user'))
                                        ->default('—')
                                        ->icon('heroicon-o-user'),

                                    TextEntry::make('booking.status')
                                        ->label(__('dashboard.resources.booking_sessions.fields.booking_status'))
                                        ->badge()
                                        ->color(fn ($state) => $state?->getColor() ?? 'gray')
                                        ->formatStateUsing(fn ($state) => $state?->getLabel() ?? '—'),

                                    TextEntry::make('booking.remaining_credits')
                                        ->label(__('dashboard.resources.booking_sessions.fields.remaining_credits'))
                                        ->numeric()
                                        ->color(fn ($state) => $state > 0 ? 'success' : 'danger')
                                        ->suffix(fn ($state, $record) => $record->booking ? " / {$record->booking->total_credits} total" : ''),
                                ]),

                            Section::make(__('dashboard.resources.booking_sessions.sections.session_details'))
                                ->icon('heroicon-o-calendar')
                                ->schema([
                                    TextEntry::make('classSession.class.name')
                                        ->label(__('dashboard.resources.booking_sessions.fields.class'))
                                        ->default('—')
                                        ->icon('heroicon-o-building-library'),

                                    TextEntry::make('classSession.date')
                                        ->label(__('dashboard.resources.booking_sessions.fields.date'))
                                        ->date('M d, Y')
                                        ->default('—')
                                        ->icon('heroicon-o-calendar'),

                                    TextEntry::make('classSession.start_time')
                                        ->label(__('dashboard.resources.booking_sessions.fields.time'))
                                        ->state(fn ($record) =>
                                            $record->classSession ?
                                            substr($record->classSession->start_time, 0, 5) . ' - ' .
                                            substr($record->classSession->end_time, 0, 5) : '—'
                                        )
                                        ->icon('heroicon-o-clock'),

                                    TextEntry::make('classSession.available_spots')
                                        ->label(__('dashboard.resources.booking_sessions.fields.available_spots'))
                                        ->state(fn ($record) => $record->classSession?->available_spots ?? '—')
                                        ->badge()
                                        ->color(fn ($state) => $state > 0 ? 'success' : 'danger'),
                                ]),
                        ]),

                        Section::make(__('dashboard.resources.booking_sessions.sections.timestamps'))
                            ->icon('heroicon-o-clock')
                            ->schema([
                                Grid::make(3)->schema([
                                    TextEntry::make('created_at')
                                        ->label(__('dashboard.resources.booking_sessions.fields.created_at'))
                                        ->dateTime('M d, Y H:i')
                                        ->placeholder('—')
                                        ->icon('heroicon-o-calendar'),

                                    TextEntry::make('updated_at')
                                        ->label(__('dashboard.resources.booking_sessions.fields.updated_at'))
                                        ->dateTime('M d, Y H:i')
                                        ->placeholder('—')
                                        ->icon('heroicon-o-arrow-path'),

                                    TextEntry::make('cancelled_at')
                                        ->label(__('dashboard.resources.booking_sessions.fields.cancelled_at'))
                                        ->dateTime('M d, Y H:i')
                                        ->placeholder(__('dashboard.resources.booking_sessions.placeholders.not_cancelled'))
                                        ->color(fn ($state) => $state ? 'danger' : 'success')
                                        ->icon(fn ($state) => $state ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle'),
                                ]),
                            ]),
                    ]),
            ]);
    }
}
