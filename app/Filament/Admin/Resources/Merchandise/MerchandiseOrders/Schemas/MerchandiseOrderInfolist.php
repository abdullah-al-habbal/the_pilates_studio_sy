<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Merchandise\MerchandiseOrders\Schemas;

use App\Models\MerchandiseOrder;
use App\Services\Currency\CurrencyService;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;

class MerchandiseOrderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        $currencyCode = app(CurrencyService::class)->getCode();

        return $schema->components([
            Grid::make(['default' => 1, 'lg' => 3])->schema([

                Section::make(__('dashboard.resources.merchandise_orders.sections.order_details'))
                    ->icon('heroicon-o-shopping-cart')
                    ->columnSpan(['default' => 1, 'lg' => 2])
                    ->columns(2)
                    ->schema([
                        TextEntry::make('merchandise_name_snapshot')
                            ->label(__('dashboard.resources.merchandise_orders.fields.merchandise'))
                            ->formatStateUsing(function ($state, MerchandiseOrder $record) {
                                if (is_array($state)) {
                                    return $state[app()->getLocale()] ?? $state['en'] ?? '—';
                                }
                                return $record->merchandise?->getTranslation('name', app()->getLocale()) ?? '—';
                            })
                            ->weight(FontWeight::Bold),

                        TextEntry::make('quantity')
                            ->label(__('dashboard.resources.merchandise_orders.fields.quantity'))
                            ->badge()
                            ->color('info'),

                        TextEntry::make('merchandise_unit_price_snapshot')
                            ->label('Unit Price (at Order)')
                            ->money($currencyCode)
                            ->placeholder('—'),

                        TextEntry::make('total_price')
                            ->label(__('dashboard.resources.merchandise_orders.fields.total_price'))
                            ->money($currencyCode)
                            ->weight(FontWeight::Bold)
                            ->color('success'),

                        TextEntry::make('paid_amount')
                            ->label('Amount Paid')
                            ->money($currencyCode)
                            ->placeholder('—'),

                        TextEntry::make('ordered_at')
                            ->label(__('dashboard.resources.merchandise_orders.fields.ordered_at'))
                            ->dateTime(),

                        TextEntry::make('exchange_rate_snapshot')
                            ->label('Exchange Rate Snapshot')
                            ->numeric(6)
                            ->placeholder('—'),
                    ]),

                Section::make(__('dashboard.resources.merchandise_orders.sections.customer'))
                    ->icon('heroicon-o-user')
                    ->columnSpan(['default' => 1, 'lg' => 1])
                    ->schema([
                        TextEntry::make('customer.fullname')
                            ->label(__('dashboard.resources.merchandise_orders.fields.customer'))
                            ->placeholder(__('dashboard.resources.merchandise_orders.placeholders.walk_in'))
                            ->weight(FontWeight::Bold),

                        TextEntry::make('customer.phone_number')
                            ->label(__('dashboard.resources.merchandise_orders.fields.phone'))
                            ->placeholder('—')
                            ->icon('heroicon-o-phone'),
                    ]),
            ]),
        ]);
    }
}
