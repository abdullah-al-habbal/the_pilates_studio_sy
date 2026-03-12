<?php

namespace App\Filament\Admin\Resources\ClassSessions\Schemas;

use App\Enums\ClassSessionStatusEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Schema;

class ClassSessionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('class_id')
                    ->relationship('class', 'title')
                    ->required(),
                DatePicker::make('date')
                    ->required(),
                TimePicker::make('start_time')
                    ->required(),
                TimePicker::make('end_time')
                    ->required(),
                TextInput::make('total_spots')
                    ->required()
                    ->numeric(),
                TextInput::make('status')
                    ->required()
                    ->default(ClassSessionStatusEnum::SCHEDULED->value),
            ]);
    }
}
