<?php

namespace App\Filament\Admin\Resources\Instructors\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class InstructorsTable
{
    public static function configure(Table $table): Table
    {
        $locale = app()->getLocale();

        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->formatStateUsing(fn ($state, $record) => $record->getTranslation('name', $locale)),
                TextColumn::make('title')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->formatStateUsing(fn ($state, $record) => $record->getTranslation('title', $locale)),
                TextColumn::make('specialty')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->formatStateUsing(fn ($state, $record) => $record->getTranslation('specialty', $locale)),
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
