<?php

namespace App\Filament\Admin\Resources\ClassSessions;

use App\Filament\Admin\Resources\ClassSessions\Pages\CreateClassSession;
use App\Filament\Admin\Resources\ClassSessions\Pages\EditClassSession;
use App\Filament\Admin\Resources\ClassSessions\Pages\ListClassSessions;
use App\Filament\Admin\Resources\ClassSessions\Pages\ViewClassSession;
use App\Filament\Admin\Resources\ClassSessions\RelationManagers\BookingSessionsRelationManager;
use App\Filament\Admin\Resources\ClassSessions\Schemas\ClassSessionForm;
use App\Filament\Admin\Resources\ClassSessions\Schemas\ClassSessionInfolist;
use App\Filament\Admin\Resources\ClassSessions\Tables\ClassSessionsTable;
use App\Models\ClassSession;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

// fix: is better to use relation manager and delete the ClassSessionResource entirlly.
class ClassSessionResource extends Resource
{
    protected static ?string $model = ClassSession::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static string|UnitEnum|null $navigationGroup = 'Schedule';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'class_id';

    public static function form(Schema $schema): Schema
    {
        return ClassSessionForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ClassSessionInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ClassSessionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            BookingSessionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListClassSessions::route('/'),
            'create' => CreateClassSession::route('/create'),
            'view' => ViewClassSession::route('/{record}'),
            'edit' => EditClassSession::route('/{record}/edit'),
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
