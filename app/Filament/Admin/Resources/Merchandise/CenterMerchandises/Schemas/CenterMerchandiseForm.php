<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Merchandise\CenterMerchandises\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CenterMerchandiseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([

            Section::make(__('dashboard.resources.center_merchandises.sections.information'))
                ->icon('heroicon-o-shopping-bag')
                ->columns(2)
                ->schema([
                    TextInput::make('name.en')
                        ->label(__('dashboard.resources.center_merchandises.fields.name') . ' (EN)')
                        ->required()
                        ->maxLength(255),

                    TextInput::make('name.ar')
                        ->label(__('dashboard.resources.center_merchandises.fields.name') . ' (AR)')
                        ->maxLength(255),

                    Textarea::make('description.en')
                        ->label(__('dashboard.resources.center_merchandises.fields.description') . ' (EN)')
                        ->rows(3)
                        ->maxLength(65535)
                        ->columnSpanFull(),

                    Textarea::make('description.ar')
                        ->label(__('dashboard.resources.center_merchandises.fields.description') . ' (AR)')
                        ->rows(3)
                        ->maxLength(65535)
                        ->columnSpanFull(),
                ]),

            Section::make(__('dashboard.resources.center_merchandises.sections.pricing'))
                ->icon('heroicon-o-currency-dollar')
                ->schema([
                    Grid::make(3)->schema([
                    Repeater::make('prices')
                        ->relationship()
                        ->schema([
                            Select::make('currency_id')
                                ->label(__('dashboard.resources.center_merchandises.fields.currency') ?? 'Currency')
                                ->relationship('currency', 'name')
                                ->required(),
                            TextInput::make('amount')
                                ->label(__('dashboard.resources.center_merchandises.fields.price') ?? 'Price')
                                ->required()
                                ->numeric()
                                ->minValue(0),
                        ])
                        ->columns(2)
                        ->label(__('dashboard.resources.center_merchandises.sections.pricing'))
                        ->columnSpanFull(),

                        TextInput::make('stock_quantity')
                            ->label(__('dashboard.resources.center_merchandises.fields.stock_quantity'))
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->minValue(fn($record): int => $record?->stock_quantity ?? 0)
                            ->helperText(
                                fn($record): ?string => $record
                                ? __('dashboard.resources.center_merchandises.helpers.stock_min', ['min' => $record->stock_quantity])
                                : null
                            ),

                        Select::make('category_id')
                            ->label(__('dashboard.resources.center_merchandises.fields.category'))
                            ->relationship('category', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->getOptionLabelFromRecordUsing(
                                fn($record) => $record->getTranslation('name', app()->getLocale())
                            ),
                    ]),
                ]),

            Section::make(__('dashboard.resources.center_merchandises.sections.gallery'))
                ->icon('heroicon-o-photo')
                ->schema([
                    Repeater::make('images')
                        ->relationship('images')
                        ->hiddenLabel()
                        ->schema([
                            FileUpload::make('url')
                                ->label(__('dashboard.resources.center_merchandises.fields.image'))
                                ->image()
                                ->directory('merchandise-images')
                                ->visibility('public')
                                ->imagePreviewHeight('120')
                                ->required()
                                ->columnSpan(2),

                            Toggle::make('is_primary')
                                ->label(__('dashboard.resources.center_merchandises.fields.is_primary'))
                                ->default(false)
                                ->columnSpan(1),
                        ])
                        ->columns(3)
                        ->addActionLabel(__('dashboard.resources.center_merchandises.actions.add_image'))
                        ->collapsible()
                        ->defaultItems(0)
                        ->reorderable()
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
