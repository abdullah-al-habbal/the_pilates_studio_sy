<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Merchandise\MerchandiseOrders\Schemas;

use Filament\Forms\Components\DateTimePicker;
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
                ->schema([
                    Select::make('merchandise_id')
                        ->label(__('dashboard.resources.merchandise_orders.fields.merchandise'))
                        ->relationship('merchandise', 'name')
                        ->required()
                        ->searchable()
                        ->preload()
                        ->live(),

                    TextInput::make('quantity')
                        ->label(__('dashboard.resources.merchandise_orders.fields.quantity'))
                        ->required()
                        ->numeric()
                        ->default(1)
                        ->minValue(1),

                    DateTimePicker::make('ordered_at')
                        ->label(__('dashboard.resources.merchandise_orders.fields.ordered_at'))
                        ->default(now())
                        ->required(),
                ]),

            Section::make(__('dashboard.resources.merchandise_orders.sections.customer'))
                ->icon('heroicon-o-user')
                ->schema([
                    Select::make('customer_id')
                        ->label(__('dashboard.resources.merchandise_orders.fields.customer'))
                        ->relationship('customer', 'fullname')
                        ->searchable()
                        ->preload()
                        ->placeholder(__('dashboard.resources.merchandise_orders.placeholders.walk_in')),
                ]),
        ]);
    }
}
