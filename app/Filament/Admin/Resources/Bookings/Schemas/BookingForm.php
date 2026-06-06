<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Bookings\Schemas;

use App\Enums\BookingStatusEnum;
use App\Models\Booking;
use App\Models\Package;
use App\Models\User;
use App\Services\Currency\CurrencyService;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
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
                        ->options(fn () => User::pluck('fullname', 'id'))
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
                                ->revealable()
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
                                $currentBookingId = null;
                                $routeRecord = request()->route('record');
                                if ($routeRecord instanceof Booking) {
                                    $currentBookingId = $routeRecord->id;
                                } elseif ($routeRecord) {
                                    $currentBookingId = (int) $routeRecord;
                                }

                                $hasBlockingBooking = Booking::query()
                                    ->where('user_id', $value)
                                    ->when($currentBookingId, fn ($q) => $q->where('id', '!=', $currentBookingId))
                                    ->where(function ($query) {
                                        $query->where(function ($q) {
                                            $q->where('status', BookingStatusEnum::ACTIVE)
                                                ->where('remaining_credits', '>', 0)
                                                ->where(function ($dateQ) {
                                                    $dateQ->whereNull('expires_at')
                                                        ->orWhere('expires_at', '>', now());
                                                });
                                        })->orWhere(function ($q) {
                                            $q->where('status', BookingStatusEnum::FROZEN)
                                                ->where(function ($dateQ) {
                                                    $dateQ->whereNull('expires_at')
                                                        ->orWhere('expires_at', '>', now());
                                                });
                                        });
                                    })
                                    ->exists();

                                if ($hasBlockingBooking) {
                                    $fail(__('dashboard.resources.bookings.validation.user_has_active_or_frozen_booking'));
                                }
                            },
                        ]),

                    Select::make('package_id')
                        ->label(__('dashboard.resources.bookings.fields.package'))
                        ->options(function () {
                            $locale = app()->getLocale();

                            return Package::query()->where('is_active', true)->get()
                                ->mapWithKeys(fn(Package $package) => [
                                    $package->id => $package->getTranslation('name', $locale)
                                        . ' (' . $package->total_credits . ' credits, '
                                        . ($package->validity_days ? $package->validity_days . ' days' : 'unlimited') . ')',
                                ]);
                        })
                        ->searchable()
                        ->preload()
                        ->required()
                        ->live()
                        ->helperText(function ($livewire) {
                            if ($livewire->record ?? null) {
                                return 'Changing the package recalculates expiry from the original purchase date and may require a price adjustment.';
                            }
                            return null;
                        })
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

                            TextInput::make('validity_days')
                                ->label('Validity Days')
                                ->numeric()
                                ->minValue(0)
                                ->default(0)
                                ->helperText('0 = unlimited'),

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
                        ->afterStateUpdated(function ($state, callable $set, $get, $livewire) {
                            if (blank($state)) {
                                $set('total_credits', 0);
                                $set('remaining_credits', 0);
                                $set('validity_days_snapshot', null);
                                $set('expires_at', null);
                                return;
                            }

                            $package = Package::select(['id', 'total_credits', 'validity_days'])->find($state);
                            if (!$package) {
                                return;
                            }

                            $set('total_credits', $package->total_credits);
                            $set('remaining_credits', $package->total_credits);
                            $set('validity_days_snapshot', $package->validity_days);

                            // CRITICAL FIX: Use created_at on edit, now() on create
                            $record = $livewire?->record ?? null;
                            $baseDate = $record?->created_at ?? now();

                            if ($package->validity_days > 0) {
                                $set('expires_at', $baseDate->copy()->addDays($package->validity_days)->format('Y-m-d H:i:s'));
                            } else {
                                $set('expires_at', null);
                            }

                            // Price impact warning on edit
                            if ($record && $record->package_id != $state) {
                                $oldPrice = $record->paid_amount ?? 0;
                                $newPrice = $package->getPriceForCurrency($record->currency_id) ?? 0;
                                $diff = $newPrice - $oldPrice;
                                $currencyCode = app(CurrencyService::class)->getCode($record->currency_id);

                                if ($diff > 0) {
                                    Notification::make()
                                        ->title('Additional Payment Required')
                                        ->body("Customer must pay an additional " . number_format($diff, 2) . " {$currencyCode}. Do NOT edit Paid Amount directly—record this as a new transaction to preserve daily balance history.")
                                        ->warning()
                                        ->persistent()
                                        ->send();
                                } elseif ($diff < 0) {
                                    Notification::make()
                                        ->title('Refund Required')
                                        ->body("Customer is owed a refund of " . number_format(abs($diff), 2) . " {$currencyCode}. Create a Refund record to keep daily balance accurate.")
                                        ->warning()
                                        ->persistent()
                                        ->send();
                                }
                            }
                        }),

                    DateTimePicker::make('expires_at')
                        ->label(__('dashboard.resources.bookings.fields.expires_at'))
                        ->withoutSeconds()
                        ->nullable()
                        ->disabled()
                        ->dehydrated()
                        ->hint(__('dashboard.resources.bookings.helpers.expiration_hint'))
                        ->hintIcon('heroicon-m-information-circle'),

                    Hidden::make('total_credits')->default(0),
                    Hidden::make('remaining_credits')->default(0),
                    Hidden::make('validity_days_snapshot'),
                    Hidden::make('currency_id')
                        ->default(fn () => app(CurrencyService::class)->getBaseCurrency()->id),
                    Hidden::make('status')->default(BookingStatusEnum::ACTIVE->value),
                ]),

            // NEW: Price Impact Section (Edit only)
            Section::make('Price Impact')
                ->icon('heroicon-o-currency-dollar')
                ->columns(3)
                ->visible(fn ($livewire) => ($livewire->record ?? null) !== null)
                ->schema([
                    Placeholder::make('_current_paid')
                        ->label('Currently Paid')
                        ->content(function ($livewire) {
                            $record = $livewire->record;
                            if (!$record) return '—';
                            $code = app(CurrencyService::class)->getCode($record->currency_id);
                            return number_format($record->paid_amount ?? 0, 2) . ' ' . $code;
                        }),

                    Placeholder::make('_new_price')
                        ->label('New Package Price')
                        ->content(function ($get, $livewire) {
                            $packageId = $get('package_id');
                            $record = $livewire->record;
                            if (!$record || !$packageId) return '—';
                            $package = Package::find($packageId);
                            if (!$package) return '—';
                            $price = $package->getPriceForCurrency($record->currency_id) ?? 0;
                            $code = app(CurrencyService::class)->getCode($record->currency_id);
                            return number_format($price, 2) . ' ' . $code;
                        }),

                    Placeholder::make('_price_diff')
                        ->label('Difference / Action')
                        ->content(function ($get, $livewire) {
                            $packageId = $get('package_id');
                            $record = $livewire->record;
                            if (!$record || !$packageId || $record->package_id == $packageId) {
                                return '—';
                            }
                            $package = Package::find($packageId);
                            if (!$package) return '—';
                            $oldPrice = $record->paid_amount ?? 0;
                            $newPrice = $package->getPriceForCurrency($record->currency_id) ?? 0;
                            $diff = $newPrice - $oldPrice;
                            $code = app(CurrencyService::class)->getCode($record->currency_id);

                            if ($diff > 0) {
                                return "Customer owes: " . number_format($diff, 2) . " {$code}";
                            } elseif ($diff < 0) {
                                return "Refund customer: " . number_format(abs($diff), 2) . " {$code}";
                            }
                            return 'No price difference';
                        }),
                ]),

            Section::make(__('dashboard.resources.bookings.sections.quick_stats'))
                ->icon('heroicon-o-chart-bar')
                ->columns(4)
                ->visible(fn ($get) => $get('total_credits') > 0)
                ->schema([
                    Placeholder::make('_credits_total')
                        ->label(__('dashboard.resources.bookings.fields.total_credits'))
                        ->content(fn ($get) => $get('total_credits') . ' credits'),

                    Placeholder::make('_credits_remaining')
                        ->label(__('dashboard.resources.bookings.fields.remaining_credits'))
                        ->content(fn ($get) => $get('remaining_credits') . ' credits'),

                    Placeholder::make('_credits_used')
                        ->label(__('dashboard.resources.bookings.fields.credits_used'))
                        ->content(fn ($get) => ($get('total_credits') - $get('remaining_credits')) . ' credits'),

                    Placeholder::make('_credits_usage')
                        ->label(__('dashboard.resources.bookings.fields.credits_usage'))
                        ->content(function ($get) {
                            $total = (int) $get('total_credits');
                            $remaining = (int) $get('remaining_credits');
                            $used = $total - $remaining;
                            $pct = $total > 0 ? round(($used / $total) * 100) : 0;

                            return "{$used} / {$total} ({$pct}%)";
                        }),
                ]),
        ]);
    }
}
