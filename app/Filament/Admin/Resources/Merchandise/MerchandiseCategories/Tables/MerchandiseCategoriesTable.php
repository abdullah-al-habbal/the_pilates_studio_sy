<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Merchandise\MerchandiseCategories\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MerchandiseCategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('dashboard.resources.merchandise_categories.fields.name'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('merchandises_count')
                    ->label(__('dashboard.resources.merchandise_categories.fields.merchandises_count'))
                    ->counts('merchandises')
                    ->badge()
                    ->color('primary')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label(__('dashboard.resources.merchandise_categories.fields.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->emptyStateHeading(__('dashboard.resources.merchandise_categories.empty_state.heading'))
            ->emptyStateIcon('heroicon-o-tag');
    }
}
