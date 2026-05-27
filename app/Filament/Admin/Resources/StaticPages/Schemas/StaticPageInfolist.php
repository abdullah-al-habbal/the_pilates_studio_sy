<?php

namespace App\Filament\Admin\Resources\StaticPages\Schemas;

use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class StaticPageInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->columns(2)
                    ->schema([
                        TextEntry::make('slug'),
                        TextEntry::make('is_active')
                            ->label('Active')
                            ->badge()
                            ->color(fn($state) => $state ? 'success' : 'danger'),
                        TextEntry::make('sort_order')
                            ->label('Sort Order'),
                    ]),
                TextEntry::make('title')
                    ->translatable(),
                TextEntry::make('content')
                    ->html()
                    ->translatable()
                    ->columnSpanFull(),
                ImageEntry::make('image'),
            ]);
    }
}
