<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Merchandise\CenterMerchandises;

use App\Filament\Admin\Resources\Merchandise\CenterMerchandises\Pages\CreateCenterMerchandise;
use App\Filament\Admin\Resources\Merchandise\CenterMerchandises\Pages\EditCenterMerchandise;
use App\Filament\Admin\Resources\Merchandise\CenterMerchandises\Pages\ListCenterMerchandises;
use App\Filament\Admin\Resources\Merchandise\CenterMerchandises\Pages\ViewCenterMerchandise;
use App\Filament\Admin\Resources\Merchandise\CenterMerchandises\RelationManagers\ImagesRelationManager;
use App\Filament\Admin\Resources\Merchandise\CenterMerchandises\Schemas\CenterMerchandiseForm;
use App\Filament\Admin\Resources\Merchandise\CenterMerchandises\Schemas\CenterMerchandiseInfolist;
use App\Filament\Admin\Resources\Merchandise\CenterMerchandises\Tables\CenterMerchandisesTable;
use App\Models\CenterMerchandise;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class CenterMerchandiseResource extends Resource
{
    protected static ?string $model = CenterMerchandise::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return __('dashboard.navigation.groups.store');
    }

    public static function getNavigationLabel(): string
    {
        return __('dashboard.resources.center_merchandises.plural');
    }

    public static function getModelLabel(): string
    {
        return __('dashboard.resources.center_merchandises.singular');
    }

    public static function getRecordTitle(?\Illuminate\Database\Eloquent\Model $record): string
    {
        return $record?->name ?? static::getModelLabel();
    }

    public static function form(Schema $schema): Schema
    {
        return CenterMerchandiseForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CenterMerchandiseInfolist::configure($schema);
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
            'view' => ViewCenterMerchandise::route('/{record}'),
            'edit' => EditCenterMerchandise::route('/{record}/edit'),
        ];
    }
}
