<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Merchandise\MerchandiseCategories;

use App\Filament\Admin\Resources\Merchandise\MerchandiseCategories\Pages\ManageMerchandiseCategories;
use App\Filament\Admin\Resources\Merchandise\MerchandiseCategories\Schemas\MerchandiseCategoryForm;
use App\Filament\Admin\Resources\Merchandise\MerchandiseCategories\Tables\MerchandiseCategoriesTable;
use App\Models\CenterMerchandiseCategory;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class MerchandiseCategoryResource extends Resource
{
    protected static ?string $model = CenterMerchandiseCategory::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-tag';

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return __('dashboard.navigation.groups.store');
    }

    public static function getNavigationLabel(): string
    {
        return __('dashboard.resources.merchandise_categories.plural');
    }

    public static function getModelLabel(): string
    {
        return __('dashboard.resources.merchandise_categories.singular');
    }

    public static function form(Schema $schema): Schema
    {
        return MerchandiseCategoryForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('dashboard.resources.merchandise_categories.sections.details'))
                ->icon('heroicon-o-tag')
                ->columns(2)
                ->schema([
                    TextEntry::make('name')
                        ->label(__('dashboard.resources.merchandise_categories.fields.name')),

                    TextEntry::make('merchandises_count')
                        ->label(__('dashboard.resources.merchandise_categories.fields.merchandises_count'))
                        ->state(fn ($record) => $record->merchandises()->count())
                        ->badge()
                        ->color('primary'),

                    TextEntry::make('created_at')
                        ->label(__('dashboard.resources.merchandise_categories.fields.created_at'))
                        ->dateTime(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return MerchandiseCategoriesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageMerchandiseCategories::route('/'),
        ];
    }
}
