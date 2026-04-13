<?php

// filePath: app/Filament/Admin/Resources/Merchandise/MerchandiseOrders/Schemas/MerchandiseOrderForm.php

namespace App\Filament\Admin\Resources\Merchandise\MerchandiseOrders\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class MerchandiseOrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('merchandise_id')
                    ->relationship('merchandise', 'name')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->reactive(),
                TextInput::make('quantity')
                    ->required()
                    ->numeric()
                    ->default(1)
                    ->minValue(1),
                Select::make('customer_id')
                    ->relationship('customer', 'fullname')
                    ->searchable()
                    ->preload()
                    ->placeholder('Walk-in Customer'),
                DateTimePicker::make('ordered_at')
                    ->default(now())
                    ->required(),
            ]);
    }
}
