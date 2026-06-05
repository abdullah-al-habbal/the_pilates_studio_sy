<?php

namespace App\Filament\Admin\Resources\RecurrencePatterns\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class RecurrencePatternForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(50)
                ->helperText('Lowercase slug: daily, weekly, biweekly, monthly'),
            TextInput::make('label')
                ->required()
                ->maxLength(50)
                ->helperText('Human-readable: Daily, Weekly, Bi-Weekly, Monthly')
                ->translatable(),
            TextInput::make('interval_days')
                ->numeric()
                ->required()
                ->minValue(1)
                ->label('Interval (Days)')
                ->helperText('1 = daily, 7 = weekly, 14 = bi-weekly, 30 = monthly'),
        ]);
    }
}
