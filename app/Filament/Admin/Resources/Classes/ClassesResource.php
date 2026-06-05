<?php

// filePath: app/Filament/Admin/Resources/Classes/ClassesResource.php

namespace App\Filament\Admin\Resources\Classes;

use App\Filament\Admin\Resources\Classes\Pages\CreateClasses;
use App\Filament\Admin\Resources\Classes\Pages\EditClasses;
use App\Filament\Admin\Resources\Classes\Pages\ListClasses;
use App\Filament\Admin\Resources\Classes\Pages\ViewClasses;
use App\Filament\Admin\Resources\Classes\RelationManagers\BookingSessionsRelationManager;
use App\Filament\Admin\Resources\Classes\RelationManagers\ImagesRelationManager;
use App\Filament\Admin\Resources\Classes\RelationManagers\SessionsRelationManager;
use App\Filament\Admin\Resources\Classes\Schemas\ClassesForm;
use App\Filament\Admin\Resources\Classes\Schemas\ClassesInfolist;
use App\Filament\Admin\Resources\Classes\Tables\ClassesTable;
use App\Models\Classes;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use LaraZeus\SpatieTranslatable\Resources\Concerns\Translatable;

class ClassesResource extends Resource
{
    use Translatable;

    protected static ?string $model = Classes::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'title';

    public static function getModelLabel(): string
    {
        return __('dashboard.resources.classes.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('dashboard.resources.classes.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('dashboard.resources.classes.navigation.label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('dashboard.navigation.groups.schedule');
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::query()
            ->where('status', 'active')
            ->count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'success';
    }

    public static function getTranslatableLocales(): array
    {
        return ['en', 'ar'];
    }

    public static function getRecordTitle(?Model $record): string
    {
        return $record?->getTranslation('title', app()->getLocale()) ?? 'Class #' . $record->id;
    }

    public static function form(Schema $schema): Schema
    {
        return ClassesForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ClassesInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ClassesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ImagesRelationManager::class,
            SessionsRelationManager::class,
            BookingSessionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListClasses::route('/'),
            'create' => CreateClasses::route('/create'),
            'view' => ViewClasses::route('/{record}'),
            'edit' => EditClasses::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'instructor:id,name',
                'category:id,name',
                'recurrencePattern:id,name,label,interval_days',
                'primaryImage',
            ])
            ->withCount([
                'sessions as upcoming_sessions_count' => function ($query) {
                    $query->where('date', '>=', now())
                        ->where('status', 'scheduled');
                },
                'sessions as total_sessions_count',
            ]);
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
