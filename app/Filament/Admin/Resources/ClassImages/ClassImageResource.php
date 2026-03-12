<?php

namespace App\Filament\Admin\Resources\ClassImages;

use App\Filament\Admin\Resources\ClassImages\Pages\CreateClassImage;
use App\Filament\Admin\Resources\ClassImages\Pages\EditClassImage;
use App\Filament\Admin\Resources\ClassImages\Pages\ListClassImages;
use App\Filament\Admin\Resources\ClassImages\Pages\ViewClassImage;
use App\Filament\Admin\Resources\ClassImages\Schemas\ClassImageForm;
use App\Filament\Admin\Resources\ClassImages\Schemas\ClassImageInfolist;
use App\Filament\Admin\Resources\ClassImages\Tables\ClassImagesTable;
use App\Models\ClassImage;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ClassImageResource extends Resource
{
    protected static ?string $model = ClassImage::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-photo';
    protected static string|UnitEnum|null $navigationGroup = 'Schedule';
    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'class_id';

    public static function form(Schema $schema): Schema
    {
        return ClassImageForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ClassImageInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ClassImagesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListClassImages::route('/'),
            'create' => CreateClassImage::route('/create'),
            'view' => ViewClassImage::route('/{record}'),
            'edit' => EditClassImage::route('/{record}/edit'),
        ];
    }
}
