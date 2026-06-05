<?php

namespace App\Filament\Admin\Resources\AppSettings\Schemas;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class AppSettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('key')
                    ->disabled()
                    ->required()
                    ->unique(ignoreRecord: true),
                Select::make('type')
                    ->options([
                        'string'    => 'String',
                        'boolean'   => 'Boolean',
                        'number'    => 'Number',
                        'image'     => 'Image',
                        'json'      => 'JSON',
                        'hex_color' => 'Hex Color',
                    ])
                    ->disabled()
                    ->required(),
                Textarea::make('value')
                    ->label('Value')
                    ->visible(fn($get) => !in_array($get('type'), ['image', 'boolean']))
                    ->required(fn($get) => !in_array($get('type'), ['image', 'boolean']))
                    ->rules([
                        fn($get) => $get('type') === 'json' ? 'json' : '',
                        fn($get) => $get('type') === 'number' ? 'numeric' : '',
                    ])
                    ->helperText(fn($get) => match ($get('type')) {
                        'json'   => 'Enter a valid JSON string, e.g. {"en":"Welcome","ar":"مرحباً"}. Translate by adding language keys.',
                        'number' => 'Enter a numeric value.',
                        default  => 'Enter a text value.',
                    }),
                Select::make('value')
                    ->label('Value')
                    ->options([
                        'true'  => 'True',
                        'false' => 'False',
                    ])
                    ->visible(fn($get) => $get('type') === 'boolean')
                    ->required(fn($get) => $get('type') === 'boolean'),
                FileUpload::make('uploaded_image')
                    ->label('Image')
                    ->disk('public')
                    ->directory('app-settings')
                    ->image()
                    ->visible(fn($get) => $get('type') === 'image')
                    ->required(fn($get) => $get('type') === 'image')
                    ->dehydrated(false)
                    ->afterStateUpdated(function (callable $set, $state) {
                        if ($state) {
                            $set('value', $state);
                        }
                    })
                    ->afterStateHydrated(function (callable $set, $record) {
                        if ($record?->type === 'image') {
                            $set('uploaded_image', $record->value);
                        }
                    }),
                ColorPicker::make('value')
                    ->label('Color')
                    ->visible(fn($get) => $get('type') === 'hex_color')
                    ->required(fn($get) => $get('type') === 'hex_color'),
                TextInput::make('description')
                    ->label('Description (optional)'),
            ]);
    }
}
