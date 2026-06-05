<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Merchandise\MerchandiseOrders\Schemas;

use App\Models\CenterMerchandise;
use App\Models\Currency;
use App\Models\User;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MerchandiseOrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([

            Section::make(__('dashboard.resources.merchandise_orders.sections.order_details'))
                ->icon('heroicon-o-shopping-cart')
                ->columns(2)
                ->schema([
                    Select::make('merchandise_id')
                        ->label(__('dashboard.resources.merchandise_orders.fields.merchandise'))
                        ->options(fn () => CenterMerchandise::all()->mapWithKeys(fn ($m) => [
                            $m->id => $m->getTranslation('name', app()->getLocale()).' (Stock: '.$m->stock_quantity.')',
                        ]))
                        ->required()
                        ->searchable()
                        ->preload()
                        ->live()
                        ->disabled(fn ($livewire) => $livewire->record !== null)
                        ->afterStateUpdated(function ($state, callable $set, $get) {
                            $set('quantity', 1);
                            $merchandise = CenterMerchandise::find($state);
                            if ($merchandise && $get('currency_id')) {
                                $price = $merchandise->getPriceForCurrency($get('currency_id')) ?? 0;
                                $set('paid_amount', $price);
                            }
                        }),

                    TextInput::make('quantity')
                        ->label(__('dashboard.resources.merchandise_orders.fields.quantity'))
                        ->required()
                        ->numeric()
                        ->default(1)
                        ->minValue(1)
                        ->maxValue(
                            fn ($get): int => $get('merchandise_id')
                            ? (CenterMerchandise::find($get('merchandise_id'))?->stock_quantity ?? 1)
                            : 1
                        )
                        ->helperText(
                            fn ($get): ?string => $get('merchandise_id')
                            ? __('dashboard.resources.merchandise_orders.helpers.max_stock', [
                                'max' => CenterMerchandise::find($get('merchandise_id'))?->stock_quantity ?? 0,
                            ])
                            : null
                        )
                        ->live()
                        ->disabled(fn ($livewire) => $livewire->record !== null)
                        ->afterStateUpdated(function ($state, callable $set, $get) {
                            $merchandise = CenterMerchandise::find($get('merchandise_id'));
                            if ($merchandise && $get('currency_id')) {
                                $unitPrice = $merchandise->getPriceForCurrency($get('currency_id')) ?? 0;
                                $set('paid_amount', $unitPrice * (int) $state);
                            }
                        }),

                    Select::make('currency_id')
                        ->label('currency')
                        ->options(fn () => Currency::where('is_active', true)->pluck('code', 'id'))
                        ->default(fn () => Currency::where('is_active', true)->value('id'))
                        ->required()
                        ->searchable()
                        ->live()
                        ->afterStateUpdated(function ($state, callable $set, $get) {
                            $merchandise = CenterMerchandise::find($get('merchandise_id'));
                            if ($merchandise && $state) {
                                $unitPrice = $merchandise->getPriceForCurrency($state) ?? 0;
                                $set('paid_amount', $unitPrice * (int) $get('quantity'));
                            }
                        }),

                    TextInput::make('paid_amount')
                        ->label('Paid Amount')
                        ->numeric()
                        ->minValue(0)
                        ->helperText('Auto-calculated from price × quantity. Override if the customer paid a different amount.'),

                    DateTimePicker::make('ordered_at')
                        ->label(__('dashboard.resources.merchandise_orders.fields.ordered_at') ?? 'Order Date')
                        ->default(now())
                        ->required(),

                    Placeholder::make('snapshot_name')
                        ->label('Item Name (at Order)')
                        ->content(function ($livewire) {
                            $record = $livewire->record;
                            if (!$record) return '—';
                            $snapshot = $record->merchandise_name_snapshot;
                            if (is_array($snapshot)) {
                                return $snapshot[app()->getLocale()] ?? $snapshot['en'] ?? '—';
                            }
                            return $record->merchandise?->getTranslation('name', app()->getLocale()) ?? '—';
                        })
                        ->visible(fn ($livewire) => $livewire->record !== null),

                    Placeholder::make('snapshot_price')
                        ->label('Unit Price (at Order)')
                        ->content(function ($livewire) {
                            $record = $livewire->record;
                            if (!$record) return '—';
                            $code = app(\App\Services\Currency\CurrencyService::class)->getCode($record->currency_id);
                            return number_format($record->merchandise_unit_price_snapshot ?? 0, 2) . ' ' . $code;
                        })
                        ->visible(fn ($livewire) => $livewire->record !== null),
                ]),

            Section::make(__('dashboard.resources.merchandise_orders.sections.customer'))
                ->icon('heroicon-o-user')
                ->schema([
                    Select::make('customer_id')
                        ->label(__('dashboard.resources.merchandise_orders.fields.customer'))
                        ->relationship('customer', 'fullname')
                        ->searchable()
                        ->preload()
                        ->placeholder(__('dashboard.resources.merchandise_orders.placeholders.walk_in'))
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
                            fn ($action) => $action
                                ->modalHeading(__('dashboard.resources.merchandise_orders.actions.create_customer'))
                                ->modalWidth('md')
                        )
                        ->createOptionUsing(function (array $data): int {
                            $data['password'] = bcrypt(filled($data['password'] ?? null) ? $data['password'] : '12345678');

                            return User::create($data)->id;
                        }),
                ]),
        ]);
    }
}
