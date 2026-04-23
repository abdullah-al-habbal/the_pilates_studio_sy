<?php
// filePath: app/Filament/Admin/Resources/BookingSessions/BookingSessionResource.php

namespace App\Filament\Admin\Resources\BookingSessions;

use App\Filament\Admin\Resources\BookingSessions\Pages\CreateBookingSession;
use App\Filament\Admin\Resources\BookingSessions\Pages\EditBookingSession;
use App\Filament\Admin\Resources\BookingSessions\Pages\ListBookingSessions;
use App\Filament\Admin\Resources\BookingSessions\Pages\ViewBookingSession;
use App\Filament\Admin\Resources\BookingSessions\Schemas\BookingSessionForm;
use App\Filament\Admin\Resources\BookingSessions\Schemas\BookingSessionInfolist;
use App\Filament\Admin\Resources\BookingSessions\Tables\BookingSessionsTable;
use App\Models\BookingSession;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class BookingSessionResource extends Resource
{
    protected static ?string $model = BookingSession::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-ticket';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'id';

    public static function getRecordTitle(?Model $record): string
    {
        if (! $record) {
            return static::getModelLabel();
        }
        $userName = $record->booking?->user?->fullname ?? '—';
        $className = $record->classSession?->class?->title[app()->getLocale()] ?? '—';
        $date = $record->classSession?->date?->format('Y-m-d') ?? '';

        return "{$userName} · {$className} · {$date}";
    }

    public static function getModelLabel(): string
    {
        return __('dashboard.resources.booking_sessions.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('dashboard.resources.booking_sessions.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('dashboard.resources.booking_sessions.navigation.label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('dashboard.navigation.groups.bookings');
    }

    public static function getNavigationBadge(): ?string
    {
        return cache()->remember(
            'filament.booking_sessions.count',
            now()->addMinutes(5),
            fn() => static::getModel()::query()->count()
        );
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }

    public static function form(Schema $schema): Schema
    {
        return BookingSessionForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return BookingSessionInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BookingSessionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBookingSessions::route('/'),
            'create' => CreateBookingSession::route('/create'),
            'view' => ViewBookingSession::route('/{record}'),
            'edit' => EditBookingSession::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'booking' => function ($query) {
                    $query->select('id', 'user_id', 'package_id', 'status', 'remaining_credits', 'total_credits')
                        ->with([
                            'user:id,fullname',
                            'package:id,name'
                        ]);
                },
                'classSession' => function ($query) {
                    $query->select('id', 'class_id', 'date', 'start_time', 'end_time', 'total_spots', 'status')
                        ->with('class:id,name');
                },
            ]);
    }

}
