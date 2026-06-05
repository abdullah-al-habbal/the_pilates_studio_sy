<?php

namespace App\Filament\Admin\Resources\AppNotifications\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;

class AppNotificationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Notification Details')
                    ->icon('heroicon-o-bell')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('type')
                            ->badge()
                            ->color(fn(string $state): string => match ($state) {
                                'image' => 'success',
                                'alert' => 'danger',
                                'promo' => 'warning',
                                default => 'gray',
                            })
                            ->icon(fn(string $state): string => match ($state) {
                                'image' => 'heroicon-o-photo',
                                'alert' => 'heroicon-o-exclamation-triangle',
                                'promo' => 'heroicon-o-megaphone',
                                default => 'heroicon-o-bell',
                            }),
                        IconEntry::make('read_at')
                            ->label('Status')
                            ->boolean()
                            ->trueIcon('heroicon-o-check-circle')
                            ->falseIcon('heroicon-o-clock')
                            ->trueColor('success')
                            ->falseColor('warning')
                            ->state(fn($record): bool => !is_null($record->read_at)),
                        TextEntry::make('title')
                            ->weight(FontWeight::Bold)
                            
                            ->columnSpanFull()
                            ->formatStateUsing(fn ($state, $record) => $record->getTranslation('title', app()->getLocale())),
                        TextEntry::make('message')
                            ->markdown()
                            ->columnSpanFull()
                            ->placeholder('No message content')
                            ->formatStateUsing(fn ($state, $record) => $record->getTranslation('message', app()->getLocale())),
                        ImageEntry::make('image')
                            ->label('Attached Image')
                            ->visible(fn($record): bool => $record->type === 'image' && filled($record->image))
                            ->height(200)
                            ->extraImgAttributes(['class' => 'rounded-lg object-cover'])
                            ->columnSpanFull(),
                    ]),
                Section::make('Recipient & Timestamps')
                    ->icon('heroicon-o-user')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('user.fullname')
                            ->label('Recipient')
                            ->icon('heroicon-o-user')
                            ->color('primary'),
                        TextEntry::make('user.email')
                            ->label('Email')
                            ->icon('heroicon-o-envelope'),
                        TextEntry::make('read_at')
                            ->label('Read At')
                            ->dateTime('M d, Y H:i')
                            ->placeholder('Not read yet')
                            ->icon('heroicon-o-check-circle')
                            ->color(fn($state) => $state ? 'success' : 'gray'),
                        TextEntry::make('created_at')
                            ->label('Sent At')
                            ->dateTime('M d, Y H:i')
                            ->icon('heroicon-o-calendar'),
                        TextEntry::make('updated_at')
                            ->label('Last Updated')
                            ->dateTime('M d, Y H:i')
                            ->icon('heroicon-o-arrow-path'),
                    ]),
            ]);
    }
}
