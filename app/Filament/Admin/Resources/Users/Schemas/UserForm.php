<?php

namespace App\Filament\Admin\Resources\Users\Schemas;

use App\Enums\UserRoleEnum;
use App\Enums\UserStatusEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Unique;

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
                    ->required()
                    ->unique(ignoreRecord: true, modifyRuleUsing: fn (Unique $rule) => $rule->where('is_active', 1)),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true, modifyRuleUsing: fn (Unique $rule) => $rule->where('is_active', 1)),
                TextInput::make('password')
                    ->password()
                    ->revealable()
                    ->required(fn (string $context) => $context === 'create')
                    ->nullable(fn (string $context) => $context === 'edit')
                    ->dehydrated(fn ($state) => filled($state))
                    ->helperText(fn (string $context) => $context === 'edit' ? 'Leave blank to keep current password.' : null),
                DatePicker::make('date_of_birth'),
                Select::make('role')
                    ->options(UserRoleEnum::options())
                    ->required()
                    ->native(false),
                Select::make('status')
                    ->options(UserStatusEnum::options())
                    ->required()
                    ->native(false),
                Toggle::make('allow_notifications')
                    ->required(),
            ]);
    }
}