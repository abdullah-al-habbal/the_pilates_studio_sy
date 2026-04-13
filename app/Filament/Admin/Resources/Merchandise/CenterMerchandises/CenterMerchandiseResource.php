<?php

namespace App\Filament\Admin\Resources\Merchandise\CenterMerchandises;

use App\Filament\Admin\Resources\Merchandise\CenterMerchandises\Pages\CreateCenterMerchandise;
use App\Filament\Admin\Resources\Merchandise\CenterMerchandises\Pages\EditCenterMerchandise;
use App\Filament\Admin\Resources\Merchandise\CenterMerchandises\Pages\ListCenterMerchandises;
use App\Filament\Admin\Resources\Merchandise\CenterMerchandises\RelationManagers\ImagesRelationManager;
use App\Filament\Admin\Resources\Merchandise\CenterMerchandises\Schemas\CenterMerchandiseForm;
use App\Filament\Admin\Resources\Merchandise\CenterMerchandises\Tables\CenterMerchandisesTable;
use App\Models\CenterMerchandise;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class CenterMerchandiseResource extends Resource
{
    protected static ?string $model = CenterMerchandise::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shopping-bag';

    protected static string|UnitEnum|null $navigationGroup = 'Store';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return CenterMerchandiseForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CenterMerchandisesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ImagesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCenterMerchandises::route('/'),
            'create' => CreateCenterMerchandise::route('/create'),
            'edit' => EditCenterMerchandise::route('/{record}/edit'),
        ];
    }
}
