<?php

namespace App\Filament\Admin\Resources\ClassCategories\Schemas;

use App\Models\ClassCategory;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;

class ClassCategoryInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(['default' => 1, 'lg' => 2])->schema([
                    Section::make('Category Details')
                        ->icon('heroicon-o-tag')
                        ->schema([
                            TextEntry::make('name')
                                ->weight(FontWeight::Bold)
                                ->formatStateUsing(fn($state, $record) =>
                                    $record->getTranslation('name', app()->getLocale())),
                            TextEntry::make('slug')
                                ->badge()
                                ->color('gray')
                                ->icon('heroicon-o-link'),
                        ]),
                    Section::make('Display')
                        ->icon('heroicon-o-paint-brush')
                        ->schema([
                            TextEntry::make('color')
                                ->badge()
                                ->color(fn(?string $state): string => $state ?? 'gray')
                                ->state(fn($record): string => $record->color ?? 'No color set')
                                ->icon('heroicon-o-swatch'),
                        ]),
                ]),
                Section::make('Statistics')
                    ->icon('heroicon-o-chart-bar')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('classes_count')
                            ->label('Linked Classes')
                            ->state(fn($record): int => $record->classes()->count())
                            ->badge()
                            ->color('info')
                            ->icon('heroicon-o-building-library'),
                    ]),
                Section::make('Timestamps')
                    ->icon('heroicon-o-clock')
                    ->collapsed()
                    ->columns(3)
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('Created')
                            ->dateTime('M d, Y H:i')
                            ->icon('heroicon-o-calendar'),
                        TextEntry::make('updated_at')
                            ->label('Updated')
                            ->dateTime('M d, Y H:i')
                            ->icon('heroicon-o-arrow-path'),
                        TextEntry::make('deleted_at')
                            ->label('Deleted')
                            ->dateTime('M d, Y H:i')
                            ->placeholder('Not deleted')
                            ->color(fn($state) => $state ? 'danger' : 'success')
                            ->icon(fn($state) => $state ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                            ->visible(fn(ClassCategory $record): bool => $record->trashed()),
                    ]),
            ]);
    }
}
