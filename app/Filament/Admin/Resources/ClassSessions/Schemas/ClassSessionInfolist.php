<?php

namespace App\Filament\Admin\Resources\ClassSessions\Schemas;

use App\Models\ClassSession;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ClassSessionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('class.title')
                    ->label('Class'),
                TextEntry::make('date')
                    ->date(),
                TextEntry::make('start_time')
                    ->time(),
                TextEntry::make('end_time')
                    ->time(),
                TextEntry::make('total_spots')
                    ->numeric(),
                TextEntry::make('status'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('deleted_at')
                    ->dateTime()
                    ->visible(fn (ClassSession $record): bool => $record->trashed()),
            ]);
    }
}
