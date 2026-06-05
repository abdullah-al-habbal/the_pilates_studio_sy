<?php

namespace App\Filament\Admin\Resources\Packages;

use App\Enums\BookingStatusEnum;
use App\Filament\Admin\Resources\Packages\Pages\CreatePackage;
use App\Filament\Admin\Resources\Packages\Pages\EditPackage;
use App\Filament\Admin\Resources\Packages\Pages\ListPackages;
use App\Filament\Admin\Resources\Packages\Pages\ViewPackage;
use App\Filament\Admin\Resources\Packages\RelationManagers\BookingsRelationManager;
use App\Filament\Admin\Resources\Packages\Schemas\PackageForm;
use App\Filament\Admin\Resources\Packages\Schemas\PackageInfolist;
use App\Filament\Admin\Resources\Packages\Tables\PackagesTable;
use App\Models\Package;
use App\Services\Currency\CurrencyService;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use LaraZeus\SpatieTranslatable\Resources\Concerns\Translatable;
use UnitEnum;

class PackageResource extends Resource
{
    use Translatable;
    protected static ?string $model = Package::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-gift';

    protected static string|UnitEnum|null $navigationGroup = 'Configuration';

    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getTranslatableLocales(): array
    {
        return ['en', 'ar'];
    }

    public static function getRecordTitle(?Model $record): string
    {
        if (!$record) {
            return static::getModelLabel();
        }

        return $record->getTranslation('name', app()->getLocale()) ?? 'Package #' . $record->id;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ])
            ->withCount('bookings')
            ->withCount([
                'bookings as active_bookings_count' => fn ($query) =>
                    $query->where('status', BookingStatusEnum::ACTIVE),
            ])
            ->withSum('bookings', 'total_credits');
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
        return PackageForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PackageInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PackagesTable::configure($table, app(CurrencyService::class)->getCode());
    }

    public static function getRelations(): array
    {
        return [
            BookingsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPackages::route('/'),
            'create' => CreatePackage::route('/create'),
            'view' => ViewPackage::route('/{record}'),
            'edit' => EditPackage::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
