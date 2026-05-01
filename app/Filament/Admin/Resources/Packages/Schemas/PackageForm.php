<?php

namespace App\Filament\Admin\Resources\Packages\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

class PackageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('total_credits')
                    ->required()
                    ->numeric(),
                Repeater::make('prices')
                    ->relationship()
                    ->schema([
                        Select::make('currency_id')
                            ->relationship('currency', 'name')
                            ->required(),
                        TextInput::make('amount')
                            ->required()
                            ->numeric()
                            ->label('Price'),
                    ])
                    ->columns(2)
                    ->label('Prices by Currency'),
            ]);
    }
}
