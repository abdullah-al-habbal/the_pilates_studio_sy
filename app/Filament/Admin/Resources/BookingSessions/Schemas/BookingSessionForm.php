<?php
// filePath: app/Filament/Admin/Resources/BookingSessions/Schemas/BookingSessionForm.php

namespace App\Filament\Admin\Resources\BookingSessions\Schemas;

use App\Enums\BookingSessionStatusEnum;
use App\Models\Booking;
use App\Models\ClassSession;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BookingSessionForm
{
    public static function configure(Schema $schema): Schema
    {
        $locale = app()->getLocale();

        return $schema
            ->components([
                Section::make(__('dashboard.resources.booking_sessions.sections.information'))
                    ->description(__('dashboard.resources.booking_sessions.sections.information_desc'))
                    ->icon('heroicon-o-ticket')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('booking_id')
                                ->label(__('dashboard.resources.booking_sessions.fields.booking'))
                                ->options(function () use ($locale) {
                                    return Booking::query()
                                        ->whereIn('status', ['active', 'exhausted'])
                                        ->with('user:id,fullname')
                                        ->get()
                                        ->mapWithKeys(function (Booking $booking) use ($locale) {
                                            return [
                                                $booking->id => "#{$booking->id} - {$booking->user?->fullname} ({$booking->remaining_credits}/{$booking->total_credits} credits)"
                                            ];
                                        });
                                })
                                ->searchable()
                                ->preload()
                                ->required()
                                ->loadingMessage(__('dashboard.messages.loading'))
                                ->searchPrompt(__('dashboard.messages.search_prompt'))
                                ->helperText(__('dashboard.resources.booking_sessions.helpers.booking'))
                                ->columnSpan(1),

                            Select::make('class_session_id')
                                ->label(__('dashboard.resources.booking_sessions.fields.class_session'))
                                ->options(function () use ($locale) {
                                    return ClassSession::query()
                                        ->where('status', 'scheduled')
                                        ->where('date', '>=', now())
                                        ->with('class')
                                        ->get()
                                        ->mapWithKeys(function (ClassSession $session) use ($locale) {
                                            $classTitle = $session->class?->getTranslation('title', $locale) ?? __('dashboard.resources.booking_sessions.placeholders.unknown_class');
                                            return [
                                                $session->id => $classTitle . ' - ' .
                                                    $session->date->format('M d, Y') . ' ' .
                                                    substr($session->start_time, 0, 5) . '-' .
                                                    substr($session->end_time, 0, 5) .
                                                    " ({$session->available_spots} " . __('dashboard.resources.booking_sessions.units.spots_left') . ")"
                                            ];
                                        });
                                })
                                ->searchable()
                                ->preload()
                                ->required()
                                ->loadingMessage(__('dashboard.messages.loading'))
                                ->searchPrompt(__('dashboard.messages.search_prompt'))
                                ->helperText(__('dashboard.resources.booking_sessions.helpers.class_session'))
                                ->columnSpan(1),

                            Select::make('status')
                                ->label(__('dashboard.resources.booking_sessions.fields.status'))
                                ->options(BookingSessionStatusEnum::options())
                                ->default(BookingSessionStatusEnum::RESERVED->value)
                                ->required()
                                ->native(false)
                                ->helperText(__('dashboard.resources.booking_sessions.helpers.status'))
                                ->columnSpan(1),

                            DateTimePicker::make('cancelled_at')
                                ->label(__('dashboard.resources.booking_sessions.fields.cancelled_at'))
                                ->displayFormat('M d, Y H:i')
                                ->nullable()
                                ->visible(function ($get) {
                                    return $get('status') === BookingSessionStatusEnum::CANCELLED->value;
                                })
                                ->helperText(__('dashboard.resources.booking_sessions.helpers.cancelled_at'))
                                ->columnSpan(1),
                        ]),
                    ]),
            ]);
    }
}
