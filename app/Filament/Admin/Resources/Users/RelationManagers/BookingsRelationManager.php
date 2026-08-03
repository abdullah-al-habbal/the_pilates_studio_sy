<?php

namespace App\Filament\Admin\Resources\Users\RelationManagers;

use App\Enums\UserRoleEnum;
use App\Filament\Admin\Resources\Bookings\BookingResource;
use App\Filament\Admin\Resources\Users\RelationManagers\Concerns\RestrictsByUserRole;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class BookingsRelationManager extends RelationManager
{
    use RestrictsByUserRole;

    protected static array $allowedOwnerRoles = [UserRoleEnum::CUSTOMER->value];

    protected static string $relationship = 'bookings';

    protected static ?string $relatedResource = BookingResource::class;

    public function table(Table $table): Table
    {
        return $table
            ->headerActions([
                CreateAction::make(),
            ]);
    }
}
