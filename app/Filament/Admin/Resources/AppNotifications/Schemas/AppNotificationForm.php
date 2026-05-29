<?php

namespace App\Filament\Admin\Resources\AppNotifications\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class AppNotificationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'fullname')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->label('Recipient'),
                Select::make('type')
                    ->options([
                        'text' => 'Text',
                        'image' => 'Image',
                        'alert' => 'Alert',
                        'promo' => 'Promotion',
                    ])
                    ->default('text')
                    ->required()
                    ->live(),
                TextInput::make('title')
                    ->required()
                    ->maxLength(255),
                Textarea::make('message')
                    ->required()
                    ->columnSpanFull()
                    ->rows(4),
                FileUpload::make('image')
                    ->image()
                    ->directory('notifications')
                    ->maxSize(2048)
                    ->visible(fn($get): bool => $get('type') === 'image')
                    ->columnSpanFull(),
            ]);
    }
}
