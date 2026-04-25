<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Bookings\Schemas;

use App\Enums\BookingStatusEnum;
use App\Models\Booking;
use App\Models\Package;
use App\Models\User;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BookingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('dashboard.resources.bookings.sections.information'))
                ->icon('heroicon-o-credit-card')
                ->columns(2)
                ->schema([

                    Select::make('user_id')
                        ->label(__('dashboard.resources.bookings.fields.user'))
                        ->options(function () {
                            return User::whereDoesntHave(
                                'bookings',
                                fn($q) => $q->where('status', BookingStatusEnum::ACTIVE)
                                    ->where('remaining_credits', '>', 0)
                            )->pluck('fullname', 'id');
                        })
                        ->searchable()
                        ->preload()
                        ->required()
                        ->createOptionForm([
                            TextInput::make('fullname')
                                ->label(__('dashboard.resources.users.fields.fullname'))
                                ->required()
                                ->maxLength(255),

                            TextInput::make('phone_number')
                                ->label(__('dashboard.resources.users.fields.phone_number'))
                                ->tel()
                                ->required()
                                ->maxLength(20),

                            TextInput::make('email')
                                ->label(__('dashboard.resources.users.fields.email'))
                                ->email()
                                ->nullable()
                                ->maxLength(255),

                            TextInput::make('password')
                                ->label(__('dashboard.resources.users.fields.password'))
                                ->password()
                                ->nullable()
                                ->maxLength(255)
                                ->helperText(__('dashboard.resources.users.helpers.password_default')),
                        ])
                        ->createOptionAction(
                            fn($action) => $action
                                ->modalHeading(__('dashboard.resources.bookings.actions.create_customer'))
                                ->modalWidth('md')
                        )
                        ->createOptionUsing(function (array $data): int {
                            $data['password'] = bcrypt(filled($data['password'] ?? null) ? $data['password'] : '12345678');

                            return User::create($data)->id;
                        })
                        ->rules([
                            'required',
                            'exists:users,id',
                            function ($attribute, $value, $fail) {
                                $exists = Booking::where('user_id', $value)
                                    ->where('status', BookingStatusEnum::ACTIVE)
                                    ->where('remaining_credits', '>', 0)
                                    ->exists();
                                if ($exists) {
                                    $fail('This user already has an active booking with credits.');
                                }
                            },
                        ]),

                    Select::make('package_id')
                        ->label(__('dashboard.resources.bookings.fields.package'))
                        ->options(function () {
                            $locale = app()->getLocale();

                            return Package::where('is_active', true)->get()
                                ->mapWithKeys(fn(Package $p) => [
                                    $p->id => $p->getTranslation('name', $locale) . ' (' . $p->total_credits . ' credits)',
                                ]);
                        })
                        ->searchable()
                        ->preload()
                        ->required()
                        ->live()
                        ->createOptionForm([
                            TextInput::make('name.en')
                                ->label('Name (EN)')
                                ->required()
                                ->maxLength(255),

                            TextInput::make('name.ar')
                                ->label('Name (AR)')
                                ->maxLength(255),

                            TextInput::make('total_credits')
                                ->label(__('dashboard.resources.bookings.fields.total_credits'))
                                ->required()
                                ->numeric()
                                ->minValue(1),

                            TextInput::make('price')
                                ->label('Price (SYP)')
                                ->required()
                                ->numeric()
                                ->prefix('SYP'),
                        ])
                        ->createOptionAction(
                            fn($action) => $action
                                ->modalHeading(__('dashboard.resources.bookings.actions.create_package'))
                                ->modalWidth('md')
                        )
                        ->createOptionUsing(function (array $data): int {
                            $data['is_active'] = true;

                            return Package::create($data)->id;
                        })
                        ->afterStateUpdated(function ($state, callable $set) {
                            if (blank($state)) {
                                return;
                            }
                            $package = Package::select(['id', 'total_credits'])->find($state);
                            if ($package) {
                                $set('total_credits', $package->total_credits);
                                $set('remaining_credits', $package->total_credits);
                            }
                        }),

                    DateTimePicker::make('expires_at')
                        ->label(__('dashboard.resources.bookings.fields.expires_at'))
                        ->withoutSeconds()
                        ->nullable()
                        ->hint('Leave empty for no expiration')
                        ->hintIcon('heroicon-m-information-circle'),

                    Hidden::make('total_credits')->default(0),
                    Hidden::make('remaining_credits')->default(0),
                    Hidden::make('status')->default(BookingStatusEnum::ACTIVE->value),
                ]),
        ]);
    }
}
