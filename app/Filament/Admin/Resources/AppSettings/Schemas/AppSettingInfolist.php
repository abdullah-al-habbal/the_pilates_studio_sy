<?php

namespace App\Filament\Admin\Resources\AppSettings\Schemas;

use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;

class AppSettingInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Setting Details')
                    ->icon('heroicon-o-cog')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('key')
                            ->weight(FontWeight::Bold)
                            ->size(TextEntry\TextEntrySize::Large)
                            ->badge()
                            ->color('gray')
                            ->copyable()
                            ->copyMessage('Copied!')
                            ->icon('heroicon-o-key'),
                        TextEntry::make('type')
                            ->badge()
                            ->color(fn($state) => match ($state) {
                                'boolean' => 'warning',
                                'number' => 'info',
                                'image' => 'success',
                                'json' => 'gray',
                                default => 'primary',
                            })
                            ->icon('heroicon-o-tag'),
                        TextEntry::make('value')
                            ->columnSpanFull()
                            ->icon('heroicon-o-variable')
                            ->fontFamily('monospace'),
                    ]),
                Section::make('Description')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        TextEntry::make('description')
                            ->placeholder('No description provided')
                            ->columnSpanFull(),
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
