<?php

namespace App\Filament\Admin\Resources\Instructors;

use App\Filament\Admin\Resources\Instructors\Pages\CreateInstructor;
use App\Filament\Admin\Resources\Instructors\Pages\EditInstructor;
use App\Filament\Admin\Resources\Instructors\Pages\ListInstructors;
use App\Filament\Admin\Resources\Instructors\Pages\ViewInstructor;
use App\Filament\Admin\Resources\Instructors\RelationManagers\ClassesRelationManager;
use App\Filament\Admin\Resources\Instructors\Schemas\InstructorForm;
use App\Filament\Admin\Resources\Instructors\Schemas\InstructorInfolist;
use App\Filament\Admin\Resources\Instructors\Tables\InstructorsTable;
use App\Models\Instructor;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use LaraZeus\SpatieTranslatable\Resources\Concerns\Translatable;
use UnitEnum;

class InstructorResource extends Resource
{
    use Translatable;
    protected static ?string $model = Instructor::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user-circle';

    protected static string|UnitEnum|null $navigationGroup = 'Configuration';

    protected static ?int $navigationSort = 3;

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
        return InstructorForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return InstructorInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InstructorsTable::configure($table);
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
            'index' => ListInstructors::route('/'),
            'create' => CreateInstructor::route('/create'),
            'view' => ViewInstructor::route('/{record}'),
            'edit' => EditInstructor::route('/{record}/edit'),
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
