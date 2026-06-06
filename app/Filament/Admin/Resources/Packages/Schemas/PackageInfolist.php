<?php

namespace App\Filament\Admin\Resources\Packages\Schemas;

use App\Models\Package;
use App\Services\Currency\CurrencyService;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;

class PackageInfolist
{
    public static function configure(Schema $schema): Schema
    {
        $locale = app()->getLocale();

        return $schema
            ->components([
                Grid::make(['default' => 1, 'lg' => 2])->schema([
                    Section::make('Package Details')
                        ->icon('heroicon-o-cube')
                        ->schema([
                            TextEntry::make('name')
                                ->weight(FontWeight::Bold)
                                ->formatStateUsing(fn($state, $record) =>
                                    $record->getTranslation('name', $locale)),
                            TextEntry::make('type')
                                ->badge()
                                ->color(fn($state): string => match ($state?->value) {
                                    'standard' => 'success',
                                    'by_system' => 'info',
                                    'for_freeze_client' => 'warning',
                                    default => 'gray',
                                })
                                ->icon('heroicon-o-tag'),
                            TextEntry::make('is_active')
                                ->label('Active')
                                ->badge()
                                ->color(fn(bool $state): string => $state ? 'success' : 'danger')
                                ->icon(fn(bool $state): string => $state ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle'),
                        ]),
                    Section::make('Credits')
                        ->icon('heroicon-o-calculator')
                        ->schema([
                            TextEntry::make('total_credits')
                                ->label('Total Sessions')
                                ->numeric()
                                ->weight(FontWeight::Bold)
                                ->color('primary')
                                ->icon('heroicon-o-numbered-list'),
                            TextEntry::make('validity_days')
                                ->label('Validity Period')
                                ->state(fn($record): string => $record->validity_days ? "{$record->validity_days} days" : 'Unlimited')
                                ->icon('heroicon-o-clock')
                                ->color(fn($state, $record): string => $record->validity_days ? 'warning' : 'success'),
                        ]),
                ]),
                Section::make('Pricing')
                    ->icon('heroicon-o-currency-dollar')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('base_price')
                            ->label('Base Price')
                            ->state(fn($record): ?int => $record->getBasePrice())
                            ->money(app(CurrencyService::class)->getBaseCurrency()->code)
                            ->weight(FontWeight::Bold)
                            ->color('success'),
                        TextEntry::make('total_bookings')
                            ->label('Total Bookings')
                            ->state(fn($record): int => $record->bookings()->count())
                            ->badge()
                            ->color('info')
                            ->icon('heroicon-o-shopping-cart'),
                    ]),
                Section::make('Features')
                    ->icon('heroicon-o-list-bullet')
                    ->collapsible()
                    ->schema([
                        TextEntry::make('features')
                            ->listWithLineBreaks()
                            ->bulleted()
                            ->placeholder('No features listed')
                            ->columnSpanFull(),
                    ]),
                Section::make('Timestamps')
                    ->icon('heroicon-o-clock')
                    ->collapsed()
                    ->columns(3)
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('Created')
                            ->dateTime('M d, Y H:i')
                            ->icon('heroicon-o-calendar'),
                        TextEntry::make('updated_at')
                            ->label('Updated')
                            ->dateTime('M d, Y H:i')
                            ->icon('heroicon-o-arrow-path'),
                        TextEntry::make('deleted_at')
                            ->label('Deleted')
                            ->dateTime('M d, Y H:i')
                            ->placeholder('Not deleted')
                            ->color(fn($state) => $state ? 'danger' : 'success')
                            ->visible(fn(Package $record): bool => $record->trashed()),
                    ]),
            ]);
    }
}
