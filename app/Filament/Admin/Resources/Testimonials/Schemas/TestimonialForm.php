<?php

namespace App\Filament\Admin\Resources\Testimonials\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

class TestimonialForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->translatable(),
                TextInput::make('role')
                    ->translatable(),
                Textarea::make('quote')
                    ->required()
                    ->columnSpanFull()
                    ->translatable(),
                FileUpload::make('avatar')
                    ->image(),
                Select::make('rating')
                    ->options([1 => '1', 2 => '2', 3 => '3', 4 => '4', 5 => '5'])
                    ->default(5)
                    ->required(),
                Toggle::make('is_active')
                    ->default(true),
                TextInput::make('sort_order')
                    ->numeric()
                    ->default(0),
            ]);
    }
}
