<?php

// filePath: app/Filament/Admin/Resources/Merchandise/MerchandiseCategories/Tables/MerchandiseCategoriesTable.php

namespace App\Filament\Admin\Resources\Merchandise\MerchandiseCategories\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MerchandiseCategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ]);
    }
}
