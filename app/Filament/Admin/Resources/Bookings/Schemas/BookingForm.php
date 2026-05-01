<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Bookings\Schemas;

use App\Enums\BookingStatusEnum;
use App\Models\Booking;
use App\Models\Package;
use App\Models\User;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
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
                                fn($query) => $query->where('status', BookingStatusEnum::ACTIVE)
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
                            $password = filled($data['password'] ?? null) ? $data['password'] : '12345678';
                            $data['password'] = bcrypt($password);

                            return User::create($data)->id;
                        })
                        ->rules([
                            'required',
                            'exists:users,id',
                            function ($attribute, $value, $fail) {
                                $hasActiveBooking = Booking::query()->where('user_id', $value)
                                    ->where('status', BookingStatusEnum::ACTIVE)
                                    ->where('remaining_credits', '>', 0)
                                    ->exists();

                                if ($hasActiveBooking) {
                                    $fail(__('dashboard.resources.bookings.validation.user_has_active_booking'));
                                }
                            },
                        ]),

                    Select::make('package_id')
                        ->label(__('dashboard.resources.bookings.fields.package'))
                        ->options(function () {
                            $locale = app()->getLocale();

                            return Package::query()->where('is_active', true)->get()
                                ->mapWithKeys(fn(Package $package) => [
                                    $package->id => $package->getTranslation('name', $locale) . ' (' . $package->total_credits . ' credits)',
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

                            Repeater::make('prices')
                                ->schema([
                                    Select::make('currency_id')
                                        ->relationship('currency', 'name')
                                        ->required(),
                                    TextInput::make('amount')
                                        ->label('Price')
                                        ->required()
                                        ->numeric()
                                        ->minValue(0),
                                ])
                                ->columns(2)
                                ->label('Prices by Currency')
                                ->minItems(1),
                        ])
                        ->createOptionAction(
                            fn($action) => $action
                                ->modalHeading(__('dashboard.resources.bookings.actions.create_package'))
                                ->modalWidth('md')
                        )
                        ->createOptionUsing(function (array $data): int {
                            $prices = $data['prices'] ?? [];
                            unset($data['prices']);

                            $data['is_active'] = true;
                            $package = Package::create($data);

                            foreach ($prices as $price) {
                                if (isset($price['currency_id']) && isset($price['amount'])) {
                                    $package->prices()->create($price);
                                }
                            }

                            return $package->id;
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
                        ->hint(__('dashboard.resources.bookings.helpers.expiration_hint'))
                        ->hintIcon('heroicon-m-information-circle'),

                    Hidden::make('total_credits')->default(0),
                    Hidden::make('remaining_credits')->default(0),
                    Hidden::make('status')->default(BookingStatusEnum::ACTIVE->value),
                ]),
        ]);
    }
}
