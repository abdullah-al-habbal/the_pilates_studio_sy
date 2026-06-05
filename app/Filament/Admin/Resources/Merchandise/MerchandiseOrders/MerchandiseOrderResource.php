<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Merchandise\MerchandiseOrders;

use App\Filament\Admin\Resources\Merchandise\MerchandiseOrders\Pages\CreateMerchandiseOrder;
use App\Filament\Admin\Resources\Merchandise\MerchandiseOrders\Pages\EditMerchandiseOrder;
use App\Filament\Admin\Resources\Merchandise\MerchandiseOrders\Pages\ListMerchandiseOrders;
use App\Filament\Admin\Resources\Merchandise\MerchandiseOrders\Pages\ViewMerchandiseOrder;
use App\Filament\Admin\Resources\Merchandise\MerchandiseOrders\Schemas\MerchandiseOrderForm;
use App\Filament\Admin\Resources\Merchandise\MerchandiseOrders\Schemas\MerchandiseOrderInfolist;
use App\Filament\Admin\Resources\Merchandise\MerchandiseOrders\Tables\MerchandiseOrdersTable;
use App\Models\MerchandiseOrder;
use App\Services\Currency\CurrencyService;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class MerchandiseOrderResource extends Resource
{
    protected static ?string $model = MerchandiseOrder::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): ?string
    {
        return __('dashboard.navigation.groups.store');
    }

    public static function getNavigationLabel(): string
    {
        return __('dashboard.resources.merchandise_orders.plural');
    }

    public static function getModelLabel(): string
    {
        return __('dashboard.resources.merchandise_orders.singular');
    }

    public static function getRecordTitle(?Model $record): string
    {
        return $record ? 'Order #' . $record->id : static::getModelLabel();
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::query()->count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'primary';
    }

    public static function form(Schema $schema): Schema
    {
        return MerchandiseOrderForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return MerchandiseOrderInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MerchandiseOrdersTable::configure($table, app(CurrencyService::class)->getCode());
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMerchandiseOrders::route('/'),
            'create' => CreateMerchandiseOrder::route('/create'),
            'view' => ViewMerchandiseOrder::route('/{record}'),
            'edit' => EditMerchandiseOrder::route('/{record}/edit'),
        ];
    }
}
