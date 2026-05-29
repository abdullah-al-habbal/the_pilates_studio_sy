<?php

namespace App\Filament\Admin\Resources\AppNotifications\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AppNotificationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.fullname')
                    ->label('User')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'image' => 'success',
                        'alert' => 'danger',
                        'promo' => 'warning',
                        default => 'gray',
                    })
                    ->searchable(),
                ImageColumn::make('image')
                    ->label('Image')
                    ->size(40)
                    ->circular()
                    ->visible(fn($record): bool => $record->type === 'image' && filled($record->image)),
                TextColumn::make('title')
                    ->searchable()
                    ->limit(40)
                    ->sortable(),
                IconColumn::make('read_at')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-clock')
                    ->trueColor('success')
                    ->falseColor('warning')
                    ->state(fn($record): bool => !is_null($record->read_at)),
                TextColumn::make('created_at')
                    ->label('Sent')
                    ->dateTime('M d, Y H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
