<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Users\RelationManagers;

use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class MerchandiseOrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'merchandiseOrders';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('dashboard.resources.merchandise_orders.plural');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('merchandise.name')
                    ->label(__('dashboard.resources.merchandise_orders.fields.merchandise'))
                    ->formatStateUsing(
                        fn ($state) => is_array($state)
                        ? ($state[app()->getLocale()] ?? $state['en'] ?? '')
                        : $state
                    )
                    ->searchable(),

                TextColumn::make('quantity')
                    ->label(__('dashboard.resources.merchandise_orders.fields.quantity'))
                    ->badge()
                    ->color('info'),

                TextColumn::make('total_price')
                    ->label(__('dashboard.resources.merchandise_orders.fields.total_price'))
                    ->state(fn ($record) => $record->quantity * ($record->merchandise?->price ?? 0))
                    ->money('SYP'),

                TextColumn::make('ordered_at')
                    ->label(__('dashboard.resources.merchandise_orders.fields.ordered_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->actions([
                ViewAction::make()
                    ->url(fn ($record) => route('filament.admin.resources.merchandise.merchandise-orders.view', $record)),
                DeleteAction::make(),
            ]);
    }
}
