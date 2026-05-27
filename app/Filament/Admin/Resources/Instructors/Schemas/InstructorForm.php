<?php

namespace App\Filament\Admin\Resources\Instructors\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class InstructorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('title'),
                TextInput::make('specialty'),
                TextInput::make('bio'),
                KeyValue::make('social_links')
                    ->label('Social Links'),
                FileUpload::make('image')
                    ->image(),
            ]);
    }
}
