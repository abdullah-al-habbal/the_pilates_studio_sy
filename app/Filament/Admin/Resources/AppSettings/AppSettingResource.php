<?php

namespace App\Filament\Admin\Resources\AppSettings;

use App\Filament\Admin\Resources\AppSettings\Pages\EditAppSetting;
use App\Filament\Admin\Resources\AppSettings\Pages\ListAppSettings;
use App\Filament\Admin\Resources\AppSettings\Pages\ViewAppSetting;
use App\Filament\Admin\Resources\AppSettings\Schemas\AppSettingForm;
use App\Filament\Admin\Resources\AppSettings\Schemas\AppSettingInfolist;
use App\Filament\Admin\Resources\AppSettings\Tables\AppSettingsTable;
use App\Models\AppSetting;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class AppSettingResource extends Resource
{
    protected static ?string $model = AppSetting::class;
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static string|UnitEnum|null $navigationGroup = 'Configuration';
    protected static ?int $navigationSort = 1;
    protected static ?string $recordTitleAttribute = 'key';

    public static function getRecordTitle(?Model $record): string
    {
        return $record?->key ?? static::getModelLabel();
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::query()->count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'primary';
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return AppSettingForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AppSettingInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AppSettingsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAppSettings::route('/'),
            'view' => ViewAppSetting::route('/{record}'),
            'edit' => EditAppSetting::route('/{record}/edit'),
        ];
    }
}
