<?php

namespace App\Filament\Admin\Resources\Packages\Schemas;

use App\Models\Package;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class PackageInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name'),
                TextEntry::make('total_credits')
                    ->numeric(),
                TextEntry::make('price')
                    ->label('Price (Default Currency)')
                    ->getStateUsing(fn($record) => $record->getPriceForCurrentCurrency())
                    ->money(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('deleted_at')
                    ->dateTime()
                    ->visible(fn (Package $record): bool => $record->trashed()),
            ]);
    }
}
