<?php

namespace App\Filament\Admin\Resources\Merchandise\MerchandiseCategories;

use App\Filament\Admin\Resources\Merchandise\MerchandiseCategories\Pages\ManageMerchandiseCategories;
use App\Filament\Admin\Resources\Merchandise\MerchandiseCategories\Schemas\MerchandiseCategoryForm;
use App\Filament\Admin\Resources\Merchandise\MerchandiseCategories\Tables\MerchandiseCategoriesTable;
use App\Models\CenterMerchandiseCategory;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class MerchandiseCategoryResource extends Resource
{
    protected static ?string $model = CenterMerchandiseCategory::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-tag';

    protected static string|UnitEnum|null $navigationGroup = 'Store';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return MerchandiseCategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MerchandiseCategoriesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageMerchandiseCategories::route('/'),
        ];
    }
}
