<?php

namespace App\Filament\Admin\Resources\Bookings\Schemas;

use App\Models\Booking;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\IconPosition;

class BookingInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                // fix: make the booking information more beature and readable, and the "Expires At" now, must hande the empty state or it have an issue
                Section::make(__('dashboard.resources.bookings.sections.information'))
                    ->description(__('dashboard.resources.bookings.sections.information_desc'))
                    ->icon('heroicon-o-information-circle')
                    ->collapsible()
                    ->schema([

                        Grid::make(2)->schema([
                            TextEntry::make('id')
                                ->label(__('dashboard.resources.bookings.fields.id'))
                                ->weight(FontWeight::Bold)
                                ->color('primary')
                                ->copyable()
                                ->copyMessage('Booking ID copied to clipboard')
                                ->copyMessageDuration(1500)
                                ->icon('heroicon-o-clipboard')
                                ->iconPosition(IconPosition::After),
                            TextEntry::make('expires_at')
                                ->label(__('dashboard.resources.bookings.fields.expires_at'))
                                ->dateTime()
                                ->color(fn($state) => $state && $state->isPast() ? 'danger' : 'success')
                                ->suffix(fn($state) => $state && $state->isPast() ? ' (Expired)' : '')
                                ->icon(fn($state) => $state && $state->isPast() ? 'heroicon-o-clock' : null),
                            Grid::make(1)->schema([
                                TextEntry::make('credits_usage_percentage')
                                    ->label('Credits Usage')
                                    ->state(fn(Booking $record) => $record->credits_usage_percentage . '% used')
                                    ->badge()
                                    ->color(fn(Booking $record) => $record->credits_progress_color)
                                    ->suffix(fn(Booking $record) => " ({$record->remaining_credits}/{$record->total_credits} remaining)"),
                            ]),

                            TextEntry::make('user.fullname')
                                ->label(__('dashboard.resources.bookings.fields.user'))
                                ->color('primary')
                                ->icon('heroicon-o-user')
                                ->iconPosition(IconPosition::Before)
                                ->state(fn(Booking $record) => $record->user?->fullname ?? '—'),

                            TextEntry::make('package.name')
                                ->label(__('dashboard.resources.bookings.fields.package'))
                                ->badge()
                                ->color('info')
                                ->icon('heroicon-o-cube')
                                ->iconPosition(IconPosition::Before)
                                ->formatStateUsing(
                                    fn($state, Booking $record) =>
                                    $record->package?->getTranslation('name', app()->getLocale()) ?? '—'
                                ),

                            TextEntry::make('status')
                                ->label(__('dashboard.resources.bookings.fields.status'))
                                ->badge()
                                ->color(fn(Booking $record) => $record->status->getColor())
                                ->icon(fn(Booking $record) => $record->status->getIcon())
                                ->formatStateUsing(fn(Booking $record) => $record->status->getLabel()),
                        ]),

                        Grid::make(3)->schema([
                            TextEntry::make('total_credits')
                                ->label(__('dashboard.resources.bookings.fields.total_credits'))
                                ->numeric()
                                ->weight(FontWeight::Bold)
                                ->color('success'),

                            TextEntry::make('remaining_credits')
                                ->label(__('dashboard.resources.bookings.fields.remaining_credits'))
                                ->numeric()
                                ->weight(FontWeight::Bold)
                                ->color(fn($state) => $state > 0 ? 'success' : 'danger'),

                            TextEntry::make('used_credits')
                                ->label(__('dashboard.resources.bookings.fields.credits_used'))
                                ->state(
                                    fn(Booking $record) =>
                                    $record->total_credits - $record->remaining_credits
                                )
                                ->numeric()
                                ->color('warning'),
                        ]),
                    ]),

                Grid::make(2)->schema([

                    Section::make(__('dashboard.resources.bookings.sections.quick_stats'))
                        ->icon('heroicon-o-chart-bar')
                        ->schema([
                            TextEntry::make('booking_sessions_count')
                                ->label(__('dashboard.resources.booking_sessions.plural'))
                                ->state(fn(Booking $record) => $record->booking_sessions_count ?? 0)
                                ->badge()
                                ->color('info'),
                        ]),

                    Section::make(__('dashboard.resources.bookings.sections.system'))
                        ->icon('heroicon-o-cog')
                        ->schema([
                            TextEntry::make('deleted_at')
                                ->label(__('dashboard.resources.bookings.fields.deleted_at'))
                                ->dateTime()
                                ->placeholder(__('dashboard.resources.bookings.placeholders.not_deleted')),
                        ]),
                ]),
            ]);
    }
}
