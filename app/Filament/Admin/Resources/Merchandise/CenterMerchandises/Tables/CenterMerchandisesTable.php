<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Merchandise\CenterMerchandises\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CenterMerchandisesTable
{
    public static function configure(Table $table, string $currencyCode): Table
    {
        return $table
            ->columns([
                ImageColumn::make('primaryImage.url')
                    ->label('')
                    ->circular()
                    ->defaultImageUrl('https://ui-avatars.com/api/?name=P&color=7C3AED&background=EDE9FE'),
                TextColumn::make('name')
                    ->label(__('dashboard.resources.center_merchandises.fields.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('category.name')
                    ->label(__('dashboard.resources.center_merchandises.fields.category'))
                    ->badge()
                    ->color('gray')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('price')
                    ->label(__('dashboard.resources.center_merchandises.fields.price'))
                    ->getStateUsing(fn($record) => $record->getBasePrice())
                    ->money($currencyCode)
                    ->sortable(),
                TextColumn::make('stock_quantity')
                    ->label(__('dashboard.resources.center_merchandises.fields.stock_quantity'))
                    ->badge()
                    ->sortable()
                    ->color(fn(int $state): string => match (true) {
                        $state === 0 => 'danger',
                        $state < 5 => 'warning',
                        default => 'success',
                    }),
                TextColumn::make('created_at')
                    ->label(__('dashboard.resources.center_merchandises.fields.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->emptyStateHeading(__('dashboard.resources.center_merchandises.empty_state.heading'))
            ->emptyStateDescription(__('dashboard.resources.center_merchandises.empty_state.description'))
            ->emptyStateIcon('heroicon-o-shopping-bag');
    }
}
