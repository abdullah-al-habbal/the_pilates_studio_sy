<?php

namespace App\Filament\Admin\Resources\ClassCategories\Schemas;

use App\Models\ClassCategory;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ClassCategoryInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('slug'),
                TextEntry::make('color')
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('deleted_at')
                    ->dateTime()
                    ->visible(fn (ClassCategory $record): bool => $record->trashed()),
            ]);
    }
}
