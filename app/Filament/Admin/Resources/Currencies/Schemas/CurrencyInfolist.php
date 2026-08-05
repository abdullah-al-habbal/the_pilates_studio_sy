<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Currencies\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;

class CurrencyInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Currency Details')
                    ->icon('heroicon-o-banknotes')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('name')
                            ->label('Name')
                            ->weight(FontWeight::Bold)
                            ->badge()
                            ->color('info'),
                        TextEntry::make('code')
                            ->label('Code')
                            ->badge()
                            ->color('primary')
                            ->icon('heroicon-o-identification'),
                        TextEntry::make('symbol')
                            ->label('Symbol')
                            ->icon('heroicon-o-banknotes'),
                        TextEntry::make('exchange_rate')
                            ->label('Exchange Rate')
                            ->numeric(decimalPlaces: 4)
                            ->icon('heroicon-o-arrows-right-left')
                            ->color('warning'),
                        TextEntry::make('decimal_places')
                            ->label('Decimal Places')
                            ->icon('heroicon-o-numbered-list'),
                        IconEntry::make('is_active')
                            ->label('Active')
                            ->boolean(),
                    ]),
                Section::make('Timestamps')
                    ->icon('heroicon-o-clock')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('Created')
                            ->dateTime('M d, Y H:i')
                            ->icon('heroicon-o-calendar'),
                        TextEntry::make('updated_at')
                            ->label('Updated')
                            ->dateTime('M d, Y H:i')
                            ->icon('heroicon-o-arrow-path'),
                    ]),
            ]);
    }
}
