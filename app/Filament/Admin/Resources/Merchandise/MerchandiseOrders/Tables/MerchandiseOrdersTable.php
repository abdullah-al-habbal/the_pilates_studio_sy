<?php

// filePath: app/Filament/Admin/Resources/Merchandise/MerchandiseOrders/Tables/MerchandiseOrdersTable.php

namespace App\Filament\Admin\Resources\Merchandise\MerchandiseOrders\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MerchandiseOrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('merchandise.name')
                    ->label('Product')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('quantity')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('total_price')
                    ->label('Total (SYP)')
                    ->state(fn ($record) => $record->quantity * ($record->merchandise?->price ?? 0))
                    ->money('SYP')
                    ->sortable(),
                TextColumn::make('customer.fullname')
                    ->label('Customer')
                    ->placeholder('Walk-in')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('ordered_at')
                    ->dateTime()
                    ->sortable(),
            ]);
    }
}
