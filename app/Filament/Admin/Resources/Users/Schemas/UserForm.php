<?php

namespace App\Filament\Admin\Resources\Users\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('fullname')
                    ->required(),
                TextInput::make('phone_number')
                    ->tel()
                    ->required(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required(),
                TextInput::make('password')
                    ->password()
                    ->required(),
                DatePicker::make('date_of_birth'),
                Toggle::make('allow_notifications')
                    ->required(),
                DateTimePicker::make('email_verified_at'),
                TextInput::make('otp_code'),
                DateTimePicker::make('otp_expires_at'),
                DateTimePicker::make('deactivated_at'),
                TextInput::make('deleted_by')
                    ->numeric(),
            ]);
    }
}
