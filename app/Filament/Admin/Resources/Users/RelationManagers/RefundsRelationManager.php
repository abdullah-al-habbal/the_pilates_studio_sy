<?php

namespace App\Filament\Admin\Resources\Users\RelationManagers;

use App\Enums\UserRoleEnum;
use App\Filament\Admin\Resources\Users\RelationManagers\Concerns\RestrictsByUserRole;
use App\Services\Currency\CurrencyService;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RefundsRelationManager extends RelationManager
{
    use RestrictsByUserRole;

    protected static array $allowedOwnerRoles = [UserRoleEnum::CUSTOMER->value];

    protected static string $relationship = 'refunds';

    protected static ?string $title = 'Refunds';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('refundable_title')
                    ->label('Source'),
                TextColumn::make('amount')
                    ->money(app(CurrencyService::class)->getBaseCurrency()->code),
                TextColumn::make('reason')
                    ->limit(40),
                TextColumn::make('refundedBy.fullname')
                    ->label('Processed By'),
                TextColumn::make('refunded_at')
                    ->dateTime(),
            ]);
    }
}
