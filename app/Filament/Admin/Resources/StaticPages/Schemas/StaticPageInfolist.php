<?php

namespace App\Filament\Admin\Resources\StaticPages\Schemas;

use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;

class StaticPageInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Page Information')
                    ->icon('heroicon-o-document')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('slug')
                            ->badge()
                            ->color('gray')
                            ->icon('heroicon-o-link')
                            ->copyable()
                            ->copyMessage('Copied!'),
                        TextEntry::make('is_active')
                            ->label('Status')
                            ->badge()
                            ->color(fn(bool $state): string => $state ? 'success' : 'danger')
                            ->icon(fn(bool $state): string => $state ? 'heroicon-o-eye' : 'heroicon-o-eye-slash'),
                        TextEntry::make('sort_order')
                            ->label('Sort Order')
                            ->numeric()
                            ->icon('heroicon-o-numbered-list')
                            ->color('gray'),
                    ]),
                Section::make('Content')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        TextEntry::make('title')
                            ->weight(FontWeight::Bold)
                            ->size(TextEntry\TextEntrySize::Large)
                            ->formatStateUsing(fn($state, $record) =>
                                $record->getTranslation('title', app()->getLocale())),
                        TextEntry::make('content')
                            ->html()
                            ->prose()
                            ->formatStateUsing(fn($state, $record) =>
                                $record->getTranslation('content', app()->getLocale()))
                            ->columnSpanFull()
                            ->placeholder('No content'),
                    ]),
                Section::make('Image')
                    ->icon('heroicon-o-photo')
                    ->schema([
                        ImageEntry::make('image')
                            ->height(200)
                            ->extraImgAttributes(['class' => 'rounded-lg object-cover'])
                            ->defaultImageUrl(url('/images/placeholder.jpg')),
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
