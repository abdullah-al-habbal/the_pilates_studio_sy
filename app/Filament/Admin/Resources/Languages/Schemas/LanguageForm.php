<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Languages\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LanguageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->columns(2)
                    ->schema([
                        TextInput::make('code')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(10)
                            ->helperText('Locale code, e.g. en, ar'),
                        TextInput::make('name')
                            ->required()
                            ->maxLength(50),
                        Select::make('direction')
                            ->options([
                                'ltr' => 'LTR',
                                'rtl' => 'RTL',
                            ])
                            ->required()
                            ->default('ltr'),
                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                        Toggle::make('is_default')
                            ->label('Default')
                            ->default(false),
                    ]),
            ]);
    }
}
