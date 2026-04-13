<?php

// filePath: app/Filament/Admin/Resources/Merchandise/CenterMerchandises/Tables/CenterMerchandisesTable.php

namespace App\Filament\Admin\Resources\Merchandise\CenterMerchandises\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CenterMerchandisesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('category.name')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('price')
                    ->numeric()
                    ->sortable()
                    ->money('SYP'),
                TextColumn::make('stock_quantity')
                    ->label('In Stock')
                    ->numeric()
                    ->sortable()
                    ->color(fn (int $state): string => $state < 5 ? 'danger' : 'success'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ]);
    }
}
