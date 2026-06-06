<?php

namespace App\Filament\Admin\Resources\Users\Schemas;

use App\Enums\UserRoleEnum;
use App\Enums\UserStatusEnum;
use App\Models\User;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // --- Identity & Contact ---
                Grid::make(['default' => 1, 'lg' => 2])->schema([
                    Section::make('Identity')
                        ->icon('heroicon-o-identification')
                        ->schema([
                            TextEntry::make('id')
                                ->label('User ID')
                                ->copyable(),
                            TextEntry::make('fullname')
                                ->weight(FontWeight::Bold)
                                ->icon('heroicon-o-user'),
                            TextEntry::make('role')
                                ->badge()
                                ->color(fn (UserRoleEnum $state) => $state->color())
                                ->formatStateUsing(fn (UserRoleEnum $state) => $state->label()),
                        ]),
                    Section::make('Contact')
                        ->icon('heroicon-o-phone')
                        ->schema([
                            TextEntry::make('email')
                                ->icon('heroicon-o-envelope')
                                ->copyable(),
                            TextEntry::make('phone_number')
                                ->icon('heroicon-o-phone'),
                            TextEntry::make('date_of_birth')
                                ->label('Date of Birth')
                                ->date('M d, Y')
                                ->placeholder('Not set')
                                ->icon('heroicon-o-cake'),
                        ]),
                ]),

                // --- Account Status ---
                Section::make('Account Status')
                    ->icon('heroicon-o-shield-check')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('status')
                            ->badge()
                            ->color(fn (UserStatusEnum $state) => $state->color())
                            ->icon(fn (UserStatusEnum $state) => $state->getIcon())
                            ->formatStateUsing(fn (UserStatusEnum $state) => $state->label()),
                        TextEntry::make('email_verified_at')
                            ->label('Email Verified')
                            ->dateTime('M d, Y H:i')
                            ->placeholder('Not verified')
                            ->icon(fn ($state) => $state ? 'heroicon-o-shield-check' : 'heroicon-o-shield-exclamation')
                            ->color(fn ($state) => $state ? 'success' : 'warning'),
                        IconEntry::make('allow_notifications')
                            ->label('Notifications')
                            ->boolean()
                            ->trueIcon('heroicon-o-bell')
                            ->falseIcon('heroicon-o-bell-slash')
                            ->state(fn (User $record): bool => $record->allow_notifications),
                    ]),

                // --- System Audit (collapsed) ---
                Section::make('System Audit')
                    ->icon('heroicon-o-cog')
                    ->collapsed()
                    ->columns(3)
                    ->schema([
                        TextEntry::make('otp_code')
                            ->label('OTP Code')
                            ->visible(fn ($state) => ! is_null($state)),
                        TextEntry::make('otp_expires_at')
                            ->label('OTP Expiry')
                            ->dateTime()
                            ->placeholder('None'),
                        TextEntry::make('deactivated_at')
                            ->label('Deactivated At')
                            ->dateTime()
                            ->placeholder('Active'),
                        TextEntry::make('deleted_by')
                            ->label('Deleted By (User ID)'),
                        TextEntry::make('frozen_at')
                            ->label('Frozen At')
                            ->dateTime()
                            ->placeholder('Never'),
                        TextEntry::make('freeze_reason')
                            ->label('Freeze Reason'),
                        TextEntry::make('created_at')
                            ->label('Member Since')
                            ->dateTime(),
                        TextEntry::make('updated_at')
                            ->label('Last Updated')
                            ->dateTime(),
                    ]),

                // --- Preferences (only if settings exist) ---
                Section::make('Preferences')
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->visible(fn (User $record) => $record->settings()->exists())
                    ->columns(2)
                    ->schema([
                        TextEntry::make('preferred_language')
                            ->label('Language')
                            ->state(fn (User $record) => $record->settings?->preferredLanguage?->name ?? 'Default'),
                        TextEntry::make('fcm_token')
                            ->label('FCM Token')
                            ->state(fn (User $record) => $record->fcm_token)
                            ->copyable()
                            ->limit(30),
                        TextEntry::make('resolved_locale')
                            ->label('Resolved Locale')
                            ->state(fn (User $record) => $record->preferred_locale),
                    ]),

                // --- Financial Summary ---
                Section::make('Financial Summary')
                    ->icon('heroicon-o-currency-dollar')
                    ->columns(4)
                    ->schema([
                        TextEntry::make('total_remaining_credits')
                            ->label('Remaining Credits')
                            ->state(fn (User $record) => $record->total_remaining_credits)
                            ->badge()
                            ->color(fn (int $state) => $state > 0 ? 'success' : 'danger'),
                        TextEntry::make('bookings_count')
                            ->label('Bookings')
                            ->state(fn (User $record) => $record->bookings()->count())
                            ->badge()
                            ->color('info'),
                        TextEntry::make('merchandise_orders_count')
                            ->label('Orders')
                            ->state(fn (User $record) => $record->merchandiseOrders()->count())
                            ->badge()
                            ->color('warning'),
                        TextEntry::make('refunds_count')
                            ->label('Refunds')
                            ->state(fn (User $record) => $record->refunds()->count())
                            ->badge()
                            ->color('gray'),
                    ]),

                // --- Activity Metrics ---
                Section::make('Activity Metrics')
                    ->icon('heroicon-o-chart-bar')
                    ->columns(4)
                    ->schema([
                        TextEntry::make('booking_sessions_count')
                            ->label('Sessions')
                            ->state(fn (User $record) => $record->bookingSessions()->count()),
                        TextEntry::make('attended_sessions_count')
                            ->label('Attended')
                            ->state(fn (User $record) => $record->bookingSessions()->where('attendance_status', 'attended')->count()),
                        TextEntry::make('missed_sessions_count')
                            ->label('Missed')
                            ->state(fn (User $record) => $record->bookingSessions()->whereIn('attendance_status', ['missed','absent'])->count()),
                        TextEntry::make('expenses_count')
                            ->label('Expenses')
                            ->state(fn (User $record) => $record->expenses()->count()),
                    ]),

                // --- Computed Flags (collapsed) ---
                Section::make('Computed Flags')
                    ->icon('heroicon-o-check-badge')
                    ->collapsed()
                    ->columns(3)
                    ->schema([
                        IconEntry::make('is_verified')
                            ->label('Email Verified')
                            ->boolean()
                            ->state(fn (User $record) => ! is_null($record->email_verified_at)),
                        IconEntry::make('has_credits')
                            ->label('Has Credits')
                            ->boolean()
                            ->state(fn (User $record) => $record->has_credits),
                        IconEntry::make('has_active_booking')
                            ->label('Active Booking')
                            ->boolean()
                            ->state(fn (User $record) => $record->has_active_booking),
                        IconEntry::make('can_book_new_package')
                            ->label('Can Book New')
                            ->boolean()
                            ->state(fn (User $record) => $record->can_book_new_package),
                        IconEntry::make('can_reserve_class')
                            ->label('Can Reserve')
                            ->boolean()
                            ->state(fn (User $record) => $record->can_reserve_class),
                        IconEntry::make('is_deactivated')
                            ->label('Deactivated')
                            ->boolean()
                            ->state(fn (User $record) => $record->is_deactivated),
                    ]),
            ]);
    }
}