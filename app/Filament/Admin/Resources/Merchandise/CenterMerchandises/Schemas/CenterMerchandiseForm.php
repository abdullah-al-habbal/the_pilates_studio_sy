<?php

// filePath: app/Filament/Admin/Resources/Merchandise/CenterMerchandises/Schemas/CenterMerchandiseForm.php

namespace App\Filament\Admin\Resources\Merchandise\CenterMerchandises\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CenterMerchandiseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Textarea::make('description')
                    ->maxLength(65535)
                    ->columnSpanFull(),
                TextInput::make('price')
                    ->required()
                    ->numeric()
                    ->prefix('SYP'),
                TextInput::make('stock_quantity')
                    ->label('Stock Quantity')
                    ->required()
                    ->numeric()
                    ->default(0),
                Select::make('category_id')
                    ->relationship('category', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
            ]);
    }
}
