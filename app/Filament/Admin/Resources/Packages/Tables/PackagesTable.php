<?php

namespace App\Filament\Admin\Resources\Packages\Tables;

use App\Enums\PackageTypeEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;

class PackagesTable
{
    public static function configure(Table $table, string $currencyCode): Table
    {
        $locale = app()->getLocale();

        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->formatStateUsing(fn ($state, $record) => $record->getTranslation('name', $locale)),

                TextColumn::make('type')
                    ->badge()
                    ->color(fn (PackageTypeEnum $state): string => $state->color())
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('total_credits')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('price')
                    ->label('Base Price')
                    ->getStateUsing(fn($record) => $record->getBasePrice())
                    ->money($currencyCode),

                TextColumn::make('bookings_count')
                    ->label('Times Used')
                    ->counts('bookings')
                    ->badge()
                    ->color('primary')
                    ->sortable(),

                TextColumn::make('active_bookings_count')
                    ->label('Active Now')
                    ->numeric()
                    ->badge()
                    ->color('success')
                    ->sortable(),

                TextColumn::make('bookings_sum_total_credits')
                    ->label('Total Credits Issued')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('features')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->headerActions([
                LocaleSwitcher::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
