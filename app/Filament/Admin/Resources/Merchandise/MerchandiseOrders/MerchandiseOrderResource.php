<?php

namespace App\Filament\Admin\Resources\Merchandise\MerchandiseOrders;

use App\Filament\Admin\Resources\Merchandise\MerchandiseOrders\Pages\CreateMerchandiseOrder;
use App\Filament\Admin\Resources\Merchandise\MerchandiseOrders\Pages\EditMerchandiseOrder;
use App\Filament\Admin\Resources\Merchandise\MerchandiseOrders\Pages\ListMerchandiseOrders;
use App\Filament\Admin\Resources\Merchandise\MerchandiseOrders\Schemas\MerchandiseOrderForm;
use App\Filament\Admin\Resources\Merchandise\MerchandiseOrders\Tables\MerchandiseOrdersTable;
use App\Models\MerchandiseOrder;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class MerchandiseOrderResource extends Resource
{
    protected static ?string $model = MerchandiseOrder::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shopping-cart';

    protected static string|UnitEnum|null $navigationGroup = 'Store';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return MerchandiseOrderForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MerchandiseOrdersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMerchandiseOrders::route('/'),
            'create' => CreateMerchandiseOrder::route('/create'),
            'edit' => EditMerchandiseOrder::route('/{record}/edit'),
        ];
    }
}
