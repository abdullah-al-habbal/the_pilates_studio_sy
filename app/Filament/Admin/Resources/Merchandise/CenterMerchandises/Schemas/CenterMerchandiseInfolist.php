<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Merchandise\CenterMerchandises\Schemas;

use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;

class CenterMerchandiseInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([

            Grid::make(['default' => 1, 'lg' => 3])->schema([

                // ── Details (left, 2/3 width) ─────────────────────────────
                Grid::make(1)
                    ->columnSpan(['default' => 1, 'lg' => 2])
                    ->schema([
                        Section::make(__('dashboard.resources.center_merchandises.sections.information'))
                            ->icon('heroicon-o-shopping-bag')
                            ->schema([
                                TextEntry::make('name')
                                    ->label(__('dashboard.resources.center_merchandises.fields.name'))
                                    ->weight(FontWeight::Bold)
                                    ->columnSpanFull(),

                                TextEntry::make('description')
                                    ->label(__('dashboard.resources.center_merchandises.fields.description'))
                                    ->placeholder(__('dashboard.resources.center_merchandises.placeholders.no_description'))
                                    ->columnSpanFull(),

                                TextEntry::make('category.name')
                                    ->label(__('dashboard.resources.center_merchandises.fields.category'))
                                    ->badge()
                                    ->color('gray'),
                            ]),

                        Section::make(__('dashboard.resources.center_merchandises.sections.pricing'))
                            ->icon('heroicon-o-currency-dollar')
                            ->columns(2)
                            ->schema([
                                TextEntry::make('price')
                                    ->label(__('dashboard.resources.center_merchandises.fields.price'))
                                    ->money('SYP')
                                    ->weight(FontWeight::Bold)
                                    ->color('success'),

                                TextEntry::make('stock_quantity')
                                    ->label(__('dashboard.resources.center_merchandises.fields.stock_quantity'))
                                    ->badge()
                                    ->color(fn (int $state): string => match (true) {
                                        $state === 0 => 'danger',
                                        $state < 5 => 'warning',
                                        default => 'success',
                                    }),
                            ]),
                    ]),

                // ── Gallery (right, 1/3 width) ────────────────────────────
                Section::make(__('dashboard.resources.center_merchandises.sections.gallery'))
                    ->icon('heroicon-o-photo')
                    ->columnSpan(['default' => 1, 'lg' => 1])
                    ->schema([
                        RepeatableEntry::make('images')
                            ->hiddenLabel()
                            ->schema([
                                ImageEntry::make('url')
                                    ->hiddenLabel()
                                    ->height(140)
                                    ->extraImgAttributes([
                                        'class' => 'w-full object-cover rounded-lg',
                                        'style' => 'aspect-ratio:1',
                                    ]),

                                TextEntry::make('is_primary')
                                    ->hiddenLabel()
                                    ->badge()
                                    ->state(fn ($record): ?string => $record->is_primary
                                        ? __('dashboard.resources.center_merchandises.labels.primary')
                                        : null
                                    )
                                    ->color('success')
                                    ->visible(fn ($record): bool => (bool) $record->is_primary),
                            ])
                            ->columns(2)
                            ->contained(false),
                    ]),
            ]),
        ]);
    }
}
