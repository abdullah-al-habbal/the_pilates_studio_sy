<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Merchandise\MerchandiseOrders;

use App\Filament\Admin\Resources\Merchandise\MerchandiseOrders\Pages\CreateMerchandiseOrder;
use App\Filament\Admin\Resources\Merchandise\MerchandiseOrders\Pages\EditMerchandiseOrder;
use App\Filament\Admin\Resources\Merchandise\MerchandiseOrders\Pages\ListMerchandiseOrders;
use App\Filament\Admin\Resources\Merchandise\MerchandiseOrders\Pages\ViewMerchandiseOrder;
use App\Filament\Admin\Resources\Merchandise\MerchandiseOrders\Schemas\MerchandiseOrderForm;
use App\Filament\Admin\Resources\Merchandise\MerchandiseOrders\Tables\MerchandiseOrdersTable;
use App\Models\MerchandiseOrder;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
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
        return cache()->remember(
            'filament.merchandise_orders.count',
            now()->addMinutes(5),
            fn() => (string) static::getModel()::query()->count()
        );
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
        return $schema->components([
            Grid::make(['default' => 1, 'lg' => 3])->schema([

                Section::make(__('dashboard.resources.merchandise_orders.sections.order_details'))
                    ->icon('heroicon-o-shopping-cart')
                    ->columnSpan(['default' => 1, 'lg' => 2])
                    ->columns(2)
                    ->schema([
                        TextEntry::make('merchandise.name')
                            ->label(__('dashboard.resources.merchandise_orders.fields.merchandise'))
                            ->weight(FontWeight::Bold),

                        TextEntry::make('quantity')
                            ->label(__('dashboard.resources.merchandise_orders.fields.quantity'))
                            ->badge()
                            ->color('info'),

                        TextEntry::make('total_price')
                            ->label(__('dashboard.resources.merchandise_orders.fields.total_price'))
                            ->state(fn($record) => $record->quantity * ($record->merchandise?->price ?? 0))
                            // fix: use the correct price approach
                            ->money('SYP')
                            ->weight(FontWeight::Bold)
                            ->color('success'),

                        TextEntry::make('ordered_at')
                            ->label(__('dashboard.resources.merchandise_orders.fields.ordered_at'))
                            ->dateTime(),
                    ]),

                Section::make(__('dashboard.resources.merchandise_orders.sections.customer'))
                    ->icon('heroicon-o-user')
                    ->columnSpan(['default' => 1, 'lg' => 1])
                    ->schema([
                        TextEntry::make('customer.fullname')
                            ->label(__('dashboard.resources.merchandise_orders.fields.customer'))
                            ->placeholder(__('dashboard.resources.merchandise_orders.placeholders.walk_in'))
                            ->weight(FontWeight::Bold),

                        TextEntry::make('customer.phone_number')
                            ->label(__('dashboard.resources.merchandise_orders.fields.phone'))
                            ->placeholder('—')
                            ->icon('heroicon-o-phone'),
                    ]),
            ]),
        ]);
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
            'view' => ViewMerchandiseOrder::route('/{record}'),
            'edit' => EditMerchandiseOrder::route('/{record}/edit'),
        ];
    }
}
