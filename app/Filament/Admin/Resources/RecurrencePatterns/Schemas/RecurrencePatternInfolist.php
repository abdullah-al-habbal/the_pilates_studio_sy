<?php

namespace App\Filament\Admin\Resources\RecurrencePatterns\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;

class RecurrencePatternInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Pattern Details')
                    ->icon('heroicon-o-arrow-path')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('name')
                            ->label('Pattern Name')
                            ->weight(FontWeight::Bold)
                            ->badge()
                            ->color('info'),
                        TextEntry::make('label')
                            ->label('Display Label')
                            ->formatStateUsing(fn($state, $record) =>
                                $record->getTranslation('label', app()->getLocale()))
                            ->placeholder('No label set')
                            ->icon('heroicon-o-tag'),
                        TextEntry::make('interval_days')
                            ->label('Interval')
                            ->state(fn($record): string => "Every {$record->interval_days} day(s)")
                            ->icon('heroicon-o-calendar')
                            ->color('warning')
                            ->weight(FontWeight::Bold),
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
                    ->columns(2)
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('Created')
                            ->dateTime('M d, Y H:i')
                            ->icon('heroicon-o-calendar'),
                        TextEntry::make('updated_at')
                            ->label('Updated')
                            ->dateTime('M d, Y H:i')
                            ->icon('heroicon-o-arrow-path'),
                    ]),
            ]);
    }
}
