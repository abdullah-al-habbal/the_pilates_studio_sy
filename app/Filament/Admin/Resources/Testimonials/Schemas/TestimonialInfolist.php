<?php

namespace App\Filament\Admin\Resources\Testimonials\Schemas;

use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;

class TestimonialInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(['default' => 1, 'lg' => 3])->schema([
                    Section::make('Avatar')
                        ->icon('heroicon-o-camera')
                        ->columnSpan(1)
                        ->schema([
                            ImageEntry::make('avatar')
                                ->height(150)
                                ->width(150)
                                ->extraImgAttributes(['class' => 'rounded-full object-cover'])
                                ->defaultImageUrl(url('/images/placeholder-person.jpg')),
                        ]),
                    Section::make('Details')
                        ->icon('heroicon-o-user')
                        ->columnSpan(2)
                        ->schema([
                            TextEntry::make('name')
                                ->weight(FontWeight::Bold)
                                ->size(TextEntry\TextEntrySize::Large)
                                ->formatStateUsing(fn($state, $record) =>
                                    $record->getTranslation('name', app()->getLocale())),
                            TextEntry::make('role')
                                ->icon('heroicon-o-briefcase')
                                ->formatStateUsing(fn($state, $record) =>
                                    $record->getTranslation('role', app()->getLocale()))
                                ->placeholder('No role specified'),
                            Grid::make(3)->schema([
                                IconEntry::make('rating')
                                    ->label('Rating')
                                    ->icon(fn(int $state): string => match (true) {
                                        $state >= 4 => 'heroicon-o-star',
                                        $state >= 2 => 'heroicon-o-star-half',
                                        default => 'heroicon-o-star',
                                    })
                                    ->color(fn(int $state): string => match (true) {
                                        $state >= 4 => 'warning',
                                        $state >= 2 => 'gray',
                                        default => 'danger',
                                    }),
                                TextEntry::make('is_active')
                                    ->label('Status')
                                    ->badge()
                                    ->color(fn(bool $state): string => $state ? 'success' : 'danger')
                                    ->icon(fn(bool $state): string => $state ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle'),
                                TextEntry::make('sort_order')
                                    ->label('Sort Order')
                                    ->numeric()
                                    ->color('gray'),
                            ]),
                        ]),
                ]),
                Section::make('Testimonial')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->schema([
                        TextEntry::make('quote')
                            ->markdown()
                            ->italic()
                            ->formatStateUsing(fn($state, $record) =>
                                '&ldquo;' . $record->getTranslation('quote', app()->getLocale()) . '&rdquo;')
                            ->columnSpanFull()
                            ->placeholder('No quote'),
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
