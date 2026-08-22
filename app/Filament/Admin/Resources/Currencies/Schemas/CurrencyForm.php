<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Currencies\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CurrencyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->columns(2)
                    ->schema([
                        TextInput::make('code')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(3)
                            ->uppercase()
                            ->helperText('ISO 4217 code, e.g. USD, SYP'),
                        TextInput::make('symbol')
                            ->required()
                            ->maxLength(10)
                            ->helperText('e.g. $, ¥'),
                        TextInput::make('decimal_places')
                            ->numeric()
                            ->required()
                            ->default(2)
                            ->minValue(0)
                            ->maxValue(4),
                        TextInput::make('exchange_rate')
                            ->numeric()
                            ->required()
                            ->default(1.0)
                            ->minValue(0.0001)
                            ->step(0.0001),
                    ]),
                TextInput::make('name')
                    ->required(),
                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),
            ]);
    }
}
