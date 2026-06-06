<?php

namespace App\Filament\Admin\Resources\Bookings\Schemas;

use App\Models\Booking;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Fieldset;
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
                Section::make(__('dashboard.resources.bookings.sections.information'))
                    ->description(__('dashboard.resources.bookings.sections.information_desc'))
                    ->icon('heroicon-o-information-circle')
                    ->columns(2)
                    ->schema([
                        Fieldset::make('Customer & Plan Details')
                            ->schema([
                                TextEntry::make('user.fullname')
                                    ->label(__('dashboard.resources.bookings.fields.user'))
                                    ->color('primary')
                                    ->weight(FontWeight::Bold)
                                    ->icon('heroicon-o-user')
                                    ->iconPosition(IconPosition::Before)
                                    ->state(fn(Booking $record) => $record->user?->fullname ?? '—'),

                                TextEntry::make('package.name')
                                    ->label(__('dashboard.resources.bookings.fields.package'))
                                    ->badge()
                                    ->color('info')
                                    ->icon('heroicon-o-cube')
                                    ->formatStateUsing(fn($state, Booking $record) => $record->package?->getTranslation('name', app()->getLocale()) ?? '—'),

                                TextEntry::make('validity_days_snapshot')
                                    ->label('Package Validity (at Purchase)')
                                    ->formatStateUsing(fn($state) => $state ? "{$state} days" : 'Unlimited')
                                    ->icon('heroicon-o-clock')
                                    ->placeholder('—'),

                                TextEntry::make('status')
                                    ->label(__('dashboard.resources.bookings.fields.status'))
                                    ->badge()
                                    ->color(fn(Booking $record) => $record->status->getColor())
                                    ->icon(fn(Booking $record) => $record->status->getIcon())
                                    ->formatStateUsing(fn(Booking $record) => $record->status->getLabel()),

                                TextEntry::make('expires_at')
                                    ->label(__('dashboard.resources.bookings.fields.expires_at'))
                                    ->placeholder(__('dashboard.placeholders.not_set'))
                                    ->dateTime()
                                    ->color(fn($state) => match (true) {
                                        $state === null => 'gray',
                                        $state->isPast() => 'danger',
                                        default => 'success',
                                    })
                                    ->suffix(fn($state) => $state && $state->isPast() ? ' (Expired)' : '')
                                    ->icon(fn($state) => $state && $state->isPast() ? 'heroicon-o-clock' : 'heroicon-o-calendar'),

                                TextEntry::make('paid_amount')
                                    ->label('Paid Amount')
                                    ->formatStateUsing(function ($state, Booking $record) {
                                        if ($state === null) return '—';
                                        $currency = $record->currency;
                                        return number_format($state, $currency->decimal_places) . ' ' . $currency->symbol;
                                    })
                                    ->icon('heroicon-o-currency-dollar'),
                            ]),

                        Fieldset::make('Credits Usage')
                            ->schema([
                                TextEntry::make('credits_usage_percentage')
                                    ->label('Usage Progress')
                                    ->state(fn(Booking $record) => $record->credits_usage_percentage . '% used')
                                    ->badge()
                                    ->color(fn(Booking $record) => $record->credits_progress_color),

                                Grid::make(3)->schema([
                                    TextEntry::make('total_credits')
                                        ->label(__('dashboard.resources.bookings.fields.total_credits'))
                                        ->numeric()
                                        ->weight(FontWeight::Bold)
                                        ->color('gray'),

                                    TextEntry::make('used_credits')
                                        ->label(__('dashboard.resources.bookings.fields.credits_used'))
                                        ->state(fn(Booking $record) => $record->total_credits - $record->remaining_credits)
                                        ->numeric()
                                        ->weight(FontWeight::Bold)
                                        ->color('warning'),

                                    TextEntry::make('remaining_credits')
                                        ->label(__('dashboard.resources.bookings.fields.remaining_credits'))
                                        ->numeric()
                                        ->weight(FontWeight::ExtraBold)
                                        ->color(fn($state) => $state > 0 ? 'success' : 'danger'),
                                ]),
                            ]),
                    ]),

                Section::make(__('dashboard.resources.bookings.sections.quick_stats'))
                    ->icon('heroicon-o-chart-bar')
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('booking_sessions_count')
                            ->label(__('dashboard.resources.booking_sessions.plural'))
                            ->state(fn(Booking $record) => $record->booking_sessions_count ?? 0)
                            ->badge()
                            ->color('info'),
                    ]),

                Section::make(__('dashboard.resources.bookings.sections.system'))
                    ->icon('heroicon-o-cog')
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('id')
                            ->label('Reference ID')
                            ->color('gray')
                            ->copyable()
                            ->icon('heroicon-o-clipboard'),

                        TextEntry::make('exchange_rate_snapshot')
                            ->label('Exchange Rate Snapshot')
                            ->formatStateUsing(function ($state, Booking $record) {
                                if ($state === null) {
                                    return '—';
                                }
                                $currency = $record->currency;
                                $code = $currency?->code;
                                $decimals = $currency?->decimal_places ?? 6;
                                return number_format($state, $decimals) . ($code ? " {$code}" : '');
                            })
                            ->icon('heroicon-o-currency-dollar'),

                        TextEntry::make('deleted_at')
                            ->label(__('dashboard.resources.bookings.fields.deleted_at'))
                            ->dateTime()
                            ->placeholder(__('dashboard.resources.bookings.placeholders.not_deleted')),
                    ]),
            ]);
    }
}
