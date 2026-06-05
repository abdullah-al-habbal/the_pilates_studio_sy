<?php

namespace App\Filament\Admin\Resources\Packages\Schemas;

use App\Enums\PackageTypeEnum;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Toggle;
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
                TextInput::make('validity_days')
                    ->label('Validity Days')
                    ->numeric()
                    ->minValue(0)
                    ->default(0)
                    ->helperText('0 = unlimited / no expiry'),
                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),
                Select::make('type')
                    ->options(PackageTypeEnum::options())
                    ->default(PackageTypeEnum::STANDARD->value),
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
                KeyValue::make('features')
                    ->label('Features'),
            ]);
    }
}
