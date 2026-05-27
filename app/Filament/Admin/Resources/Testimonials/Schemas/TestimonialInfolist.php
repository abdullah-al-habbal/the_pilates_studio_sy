<?php

namespace App\Filament\Admin\Resources\Testimonials\Schemas;

use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class TestimonialInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name'),
                TextEntry::make('role'),
                TextEntry::make('quote')
                    ->columnSpanFull(),
                ImageEntry::make('avatar')
                    ->placeholder('-'),
                TextEntry::make('rating'),
                TextEntry::make('is_active')
                    ->badge()
                    ->color(fn($state) => $state ? 'success' : 'danger'),
                TextEntry::make('sort_order'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
