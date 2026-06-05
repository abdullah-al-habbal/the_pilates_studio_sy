<?php

namespace App\Filament\Admin\Resources\AppNotifications;

use App\Filament\Admin\Resources\AppNotifications\Pages\ListAppNotifications;
use App\Filament\Admin\Resources\AppNotifications\Pages\ViewAppNotification;
use App\Filament\Admin\Resources\AppNotifications\Schemas\AppNotificationForm;
use App\Filament\Admin\Resources\AppNotifications\Schemas\AppNotificationInfolist;
use App\Filament\Admin\Resources\AppNotifications\Tables\AppNotificationsTable;
use App\Models\AppNotification;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class AppNotificationResource extends Resource
{
    protected static ?string $model = AppNotification::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-bell';

    protected static string|UnitEnum|null $navigationGroup = 'Users';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'title';

    public static function getRecordTitle(?Model $record): string
    {
        return $record?->getTranslation('title', app()->getLocale()) ?? '#' . $record->id;
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
        return AppNotificationForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AppNotificationInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AppNotificationsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAppNotifications::route('/'),
            'view' => ViewAppNotification::route('/{record}'),
        ];
    }
}