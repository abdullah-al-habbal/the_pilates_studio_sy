<?php

namespace App\Filament\Admin\Resources\Users\RelationManagers;

use App\Services\Currency\CurrencyService;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ExpensesRelationManager extends RelationManager
{
    protected static string $relationship = 'expenses';
    protected static ?string $title = 'Expenses';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('category'),
                TextColumn::make('amount')
                    ->money(app(CurrencyService::class)->getBaseCurrency()->code),
                TextColumn::make('description')
                    ->limit(50),
                TextColumn::make('created_at')
                    ->label('Recorded')
                    ->dateTime(),
            ]);
    }
}
