<?php

namespace App\Filament\Admin\Resources\StaticPages;

use App\Filament\Admin\Resources\StaticPages\Pages\CreateStaticPage;
use App\Filament\Admin\Resources\StaticPages\Pages\EditStaticPage;
use App\Filament\Admin\Resources\StaticPages\Pages\ListStaticPages;
use App\Filament\Admin\Resources\StaticPages\Pages\ViewStaticPage;
use App\Filament\Admin\Resources\StaticPages\Schemas\StaticPageForm;
use App\Filament\Admin\Resources\StaticPages\Schemas\StaticPageInfolist;
use App\Filament\Admin\Resources\StaticPages\Tables\StaticPagesTable;
use App\Models\StaticPage;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class StaticPageResource extends Resource
{
    protected static ?string $model = StaticPage::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';
    protected static string|UnitEnum|null $navigationGroup = 'Configuration';
    protected static ?int $navigationSort = 5;

    protected static ?string $recordTitleAttribute = 'slug';

    public static function form(Schema $schema): Schema
    {
        return StaticPageForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return StaticPageInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StaticPagesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStaticPages::route('/'),
            'create' => CreateStaticPage::route('/create'),
            'view' => ViewStaticPage::route('/{record}'),
            'edit' => EditStaticPage::route('/{record}/edit'),
        ];
    }
}
