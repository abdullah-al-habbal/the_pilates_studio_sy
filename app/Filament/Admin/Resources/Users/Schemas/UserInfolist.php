<?php

namespace App\Filament\Admin\Resources\Users\Schemas;

use App\Enums\UserStatusEnum;
use App\Models\User;
use Filament\Schemas\Components\Grid;
use Filament\Infolists\Components\IconEntry;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\IconPosition;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(['default' => 1, 'lg' => 2])->schema([
                    Section::make('Account Information')
                        ->icon('heroicon-o-user')
                        ->schema([
                            TextEntry::make('fullname')
                                ->weight(FontWeight::Bold)
                                
                                ->icon('heroicon-o-user'),
                            TextEntry::make('email')
                                ->icon('heroicon-o-envelope')
                                ->copyable()
                                ->copyMessage('Copied!'),
                            TextEntry::make('phone_number')
                                ->icon('heroicon-o-phone'),
                            TextEntry::make('date_of_birth')
                                ->label('Date of Birth')
                                ->date('M d, Y')
                                ->placeholder('Not set')
                                ->icon('heroicon-o-cake'),
                        ]),
                    Section::make('Account Status')
                        ->icon('heroicon-o-shield-check')
                        ->schema([
                            TextEntry::make('status')
                                ->badge()
                                ->color(fn(UserStatusEnum $state): string => $state->getColor())
                                ->icon(fn(UserStatusEnum $state): ?string => $state->getIcon())
                                ->formatStateUsing(fn(UserStatusEnum $state): string => $state->getLabel()),
                            TextEntry::make('email_verified_at')
                                ->label('Email Verified')
                                ->dateTime('M d, Y H:i')
                                ->placeholder('Not verified')
                                ->icon(fn($state) => $state ? 'heroicon-o-shield-check' : 'heroicon-o-shield-exclamation')
                                ->color(fn($state) => $state ? 'success' : 'warning'),
                            IconEntry::make('allow_notifications')
                                ->label('Notifications')
                                ->boolean()
                                ->trueIcon('heroicon-o-bell')
                                ->falseIcon('heroicon-o-bell-slash')
                                ->state(fn($record): bool => (bool) $record->allow_notifications),
                        ]),
                ]),
                Section::make('Activity Summary')
                    ->icon('heroicon-o-chart-bar')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('total_remaining_credits')
                            ->label('Remaining Credits')
                            ->numeric()
                            ->badge()
                            ->color(fn(int $state): string => $state > 0 ? 'success' : 'danger'),
                        TextEntry::make('bookings_count')
                            ->label('Total Bookings')
                            ->state(fn($record): int => $record->bookings()->count())
                            ->badge()
                            ->color('info'),
                        TextEntry::make('merchandise_orders_count')
                            ->label('Store Orders')
                            ->state(fn($record): int => $record->merchandiseOrders()->count())
                            ->badge()
                            ->color('warning'),
                    ]),
                Section::make('System Info')
                    ->icon('heroicon-o-cog')
                    ->collapsed()
                    ->columns(3)
                    ->schema([
                        TextEntry::make('id')
                            ->label('User ID')
                            ->copyable()
                            ->icon('heroicon-o-clipboard'),
                        TextEntry::make('created_at')
                            ->label('Member Since')
                            ->dateTime('M d, Y H:i')
                            ->icon('heroicon-o-calendar'),
                        TextEntry::make('deleted_at')
                            ->label('Deactivated')
                            ->dateTime('M d, Y H:i')
                            ->placeholder('Active')
                            ->color(fn($state) => $state ? 'danger' : 'success')
                            ->visible(fn(User $record): bool => $record->trashed()),
                    ]),
            ]);
    }
}
