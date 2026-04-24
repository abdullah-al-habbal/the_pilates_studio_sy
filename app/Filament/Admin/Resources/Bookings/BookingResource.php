<?php

// filePath: app/Filament/Admin/Resources/Bookings/BookingResource.php

namespace App\Filament\Admin\Resources\Bookings;

use App\Enums\AttendanceStatusEnum;
use App\Enums\BookingSessionStatusEnum;
use App\Filament\Admin\Resources\Bookings\Pages\CreateBooking;
use App\Filament\Admin\Resources\Bookings\Pages\EditBooking;
use App\Filament\Admin\Resources\Bookings\Pages\ListBookings;
use App\Filament\Admin\Resources\Bookings\Pages\ViewBooking;
use App\Filament\Admin\Resources\Bookings\RelationManagers\BookingSessionsRelationManager;
use App\Filament\Admin\Resources\Bookings\Schemas\BookingForm;
use App\Filament\Admin\Resources\Bookings\Schemas\BookingInfolist;
use App\Filament\Admin\Resources\Bookings\Tables\BookingsTable;
use App\Models\Booking;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BookingResource extends Resource
{
    protected static ?string $model = Booking::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-credit-card';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'id';

    public static function getRecordTitle(?Model $record): string
    {
        if (!$record) {
            return static::getModelLabel();
        }
        $userName = $record->user?->fullname ?? 'Unknown User';
        $packageName = $record->package?->getTranslation('name', app()->getLocale()) ?? 'Unknown Package';

        return "{$userName} — {$packageName}";
    }

    public static function getModelLabel(): string
    {
        return __('dashboard.resources.bookings.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('dashboard.resources.bookings.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('dashboard.resources.bookings.navigation.label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('dashboard.navigation.groups.bookings');
    }

    public static function getNavigationBadge(): ?string
    {
        return cache()->remember(
            'filament.bookings.count',
            now()->addMinutes(5),
            fn() => (string) static::getModel()::query()->count()
        );
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'primary';
    }

    public static function form(Schema $schema): Schema
    {
        return BookingForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return BookingInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BookingsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            BookingSessionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBookings::route('/'),
            'create' => CreateBooking::route('/create'),
            'view' => ViewBooking::route('/{record}'),
            'edit' => EditBooking::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'user:id,fullname',
                'package:id,name',
            ])
            ->withCount([
                'bookingSessions',
                'bookingSessions as reserved_sessions_count' => function ($query) {
                    $query->where('status', BookingSessionStatusEnum::RESERVED);
                },
                'bookingSessions as attended_sessions_count' => function ($query) {
                    $query->where('attendance_status', AttendanceStatusEnum::ATTENDED);
                },
            ]);
    }
}
