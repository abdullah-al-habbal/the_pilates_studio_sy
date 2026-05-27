<?php

namespace App\Filament\Admin\Resources\AppSettings\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class AppSettingInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('key'),
                TextEntry::make('value'),
                TextEntry::make('type')
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'boolean' => 'warning',
                        'number' => 'info',
                        'image' => 'success',
                        'json' => 'gray',
                        default => 'primary',
                    }),
                TextEntry::make('description')
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
