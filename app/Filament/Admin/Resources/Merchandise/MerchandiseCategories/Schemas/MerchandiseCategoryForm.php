<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Merchandise\MerchandiseCategories\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MerchandiseCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('dashboard.resources.merchandise_categories.sections.details'))
                ->icon('heroicon-o-tag')
                ->columns(2)
                ->schema([
                    TextInput::make('name.en')
                        ->label(__('dashboard.resources.merchandise_categories.fields.name').' (EN)')
                        ->required()
                        ->maxLength(255),

                    TextInput::make('name.ar')
                        ->label(__('dashboard.resources.merchandise_categories.fields.name').' (AR)')
                        ->maxLength(255),
                ]),
        ]);
    }
}
