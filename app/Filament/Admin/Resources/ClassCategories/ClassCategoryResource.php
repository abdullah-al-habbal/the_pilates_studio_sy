<?php

namespace App\Filament\Admin\Resources\ClassCategories;

use App\Filament\Admin\Resources\ClassCategories\Pages\CreateClassCategory;
use App\Filament\Admin\Resources\ClassCategories\Pages\EditClassCategory;
use App\Filament\Admin\Resources\ClassCategories\Pages\ListClassCategories;
use App\Filament\Admin\Resources\ClassCategories\Pages\ViewClassCategory;
use App\Filament\Admin\Resources\ClassCategories\RelationManagers\ClassesRelationManager;
use App\Filament\Admin\Resources\ClassCategories\Schemas\ClassCategoryForm;
use App\Filament\Admin\Resources\ClassCategories\Schemas\ClassCategoryInfolist;
use App\Filament\Admin\Resources\ClassCategories\Tables\ClassCategoriesTable;
use App\Models\ClassCategory;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use LaraZeus\SpatieTranslatable\Resources\Concerns\Translatable;
use UnitEnum;

class ClassCategoryResource extends Resource
{
    use Translatable;
    protected static ?string $model = ClassCategory::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-tag';

    protected static string|UnitEnum|null $navigationGroup = 'Configuration';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getTranslatableLocales(): array
    {
        return ['en', 'ar'];
    }

    public static function getRecordTitle(?Model $record): string
    {
        return $record?->getTranslation('name', app()->getLocale()) ?? static::getModelLabel();
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
        return ClassCategoryForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ClassCategoryInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ClassCategoriesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ClassesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListClassCategories::route('/'),
            'create' => CreateClassCategory::route('/create'),
            'view' => ViewClassCategory::route('/{record}'),
            'edit' => EditClassCategory::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ])
            ->withCount('classes');
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ])
            ->withCount('classes');
    }
}
