<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Merchandise\MerchandiseOrders\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MerchandiseOrdersTable
{
    public static function configure(Table $table, string $currencyCode): Table
    {
        return $table
            ->columns([
                TextColumn::make('merchandise_name_snapshot')
                    ->label(__('dashboard.resources.merchandise_orders.fields.merchandise'))
                    ->formatStateUsing(function ($state, $record) {
                        if (is_array($state)) {
                            return $state[app()->getLocale()] ?? $state['en'] ?? '—';
                        }
                        return $record->merchandise?->getTranslation('name', app()->getLocale()) ?? '—';
                    })
                    ->searchable()
                    ->sortable(),
                TextColumn::make('quantity')
                    ->label(__('dashboard.resources.merchandise_orders.fields.quantity'))
                    ->badge()->color('info')->sortable(),
                TextColumn::make('total_price')
                    ->label(__('dashboard.resources.merchandise_orders.fields.total_price'))
                    ->state(fn ($record) => $record->total_price)
                    ->money($currencyCode)
                    ->sortable(),
                TextColumn::make('customer.fullname')
                    ->label(__('dashboard.resources.merchandise_orders.fields.customer'))
                    ->placeholder(__('dashboard.resources.merchandise_orders.placeholders.walk_in'))
                    ->searchable()->sortable(),
                TextColumn::make('ordered_at')
                    ->label(__('dashboard.resources.merchandise_orders.fields.ordered_at'))
                    ->dateTime()->sortable(),
                TextColumn::make('exchange_rate_snapshot')
                    ->label('Rate')
                    ->numeric(decimalPlaces: 6)
                    ->default('-')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([ViewAction::make(), EditAction::make(), DeleteAction::make()])
            ->emptyStateHeading(__('dashboard.resources.merchandise_orders.empty_state.heading'))
            ->emptyStateIcon('heroicon-o-shopping-cart');
    }
}
