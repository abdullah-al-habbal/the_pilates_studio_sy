<?php

namespace App\Filament\Admin\Resources\Bookings\Schemas;

use App\Enums\BookingStatusEnum;
use App\Models\Package;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BookingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('dashboard.resources.bookings.sections.information'))
                    ->icon('heroicon-o-credit-card')
                    ->columns(2)
                    ->schema([
                        Select::make('user_id')
                            ->label(__('dashboard.resources.bookings.fields.user'))
                            ->relationship('user', 'fullname')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->columnSpan(1),

                        Select::make('package_id')
                            ->label(__('dashboard.resources.bookings.fields.package'))
                            ->options(function () {
                                $locale = app()->getLocale();
                                return Package::query()
                                    ->where('is_active', true)
                                    ->get(['id', 'name'])
                                    ->mapWithKeys(fn (Package $package) => [
                                        $package->id => $package->getTranslation('name', $locale),
                                    ]);
                            })
                            ->searchable()
                            ->preload()
                            ->required()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if (blank($state)) {
                                    return;
                                }
                                $package = Package::query()
                                    ->select(['id', 'total_credits'])
                                    ->find($state);
                                if ($package) {
                                    $set('total_credits', $package->total_credits);
                                    $set('remaining_credits', $package->total_credits);
                                }
                            })
                            ->loadingMessage('Loading packages...')
                            ->searchPrompt('Search by package name...')
                            ->columnSpan(1),

                        DateTimePicker::make('expires_at')
                            ->label(__('dashboard.resources.bookings.fields.expires_at'))
                            ->withoutSeconds()
                            ->displayFormat('M d, Y H:i')
                            ->placeholder(__('dashboard.resources.bookings.placeholders.expires_at'))
                            ->hint('Leave empty for no expiration')
                            ->hintIcon('heroicon-m-information-circle')
                            ->nullable()
                            ->columnSpan(1)
                            ->helperText(function ($state) {
                                if ($state && $state->isPast()) {
                                    return '⚠️ This date is in the past';
                                }
                                return null;
                            }),

                        Hidden::make('total_credits')
                            ->default(0),

                        Hidden::make('remaining_credits')
                            ->default(0),

                        Hidden::make('status')
                            ->default(BookingStatusEnum::ACTIVE->value),
                    ]),
            ]);
    }
}
