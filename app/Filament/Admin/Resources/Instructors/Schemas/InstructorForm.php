<?php

namespace App\Filament\Admin\Resources\Instructors\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
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
                Repeater::make('social_links')
                    ->label('Social Links')
                    ->schema([
                        TextInput::make('platform')
                            ->label('Platform')
                            ->required()
                            ->helperText('e.g. instagram, facebook, twitter, youtube, linkedin, tiktok'),
                        TextInput::make('url')
                            ->label('URL')
                            ->required()
                            ->url(),
                    ])
                    ->defaultItems(0)
                    ->addActionLabel('Add social link'),
                FileUpload::make('image')
                    ->image(),
            ]);
    }
}
