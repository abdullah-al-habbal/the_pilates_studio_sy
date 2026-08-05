<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Languages;

use App\Filament\Admin\Resources\Languages\Pages\CreateLanguage;
use App\Filament\Admin\Resources\Languages\Pages\EditLanguage;
use App\Filament\Admin\Resources\Languages\Pages\ListLanguages;
use App\Filament\Admin\Resources\Languages\Pages\ViewLanguage;
use App\Filament\Admin\Resources\Languages\Schemas\LanguageForm;
use App\Filament\Admin\Resources\Languages\Schemas\LanguageInfolist;
use App\Filament\Admin\Resources\Languages\Tables\LanguagesTable;
use App\Models\Language;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class LanguageResource extends Resource
{
    protected static ?string $model = Language::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-language';

    protected static string|UnitEnum|null $navigationGroup = 'Configuration';

    protected static ?int $navigationSort = 6;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getRecordTitle(?Model $record): string
    {
        return $record?->name ?? static::getModelLabel();
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
        return LanguageForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return LanguageInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LanguagesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLanguages::route('/'),
            'create' => CreateLanguage::route('/create'),
            'view' => ViewLanguage::route('/{record}'),
            'edit' => EditLanguage::route('/{record}/edit'),
        ];
    }
}
