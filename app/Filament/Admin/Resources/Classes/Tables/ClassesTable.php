<?php
// filePath: app/Filament/Admin/Resources/Classes/Tables/ClassesTable.php

namespace App\Filament\Admin\Resources\Classes\Tables;

use App\Enums\ClassStatusEnum;
use App\Models\Instructor;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;

class ClassesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('primaryImage.url')
                    ->label(__('dashboard.resources.classes.fields.image'))
                    ->circular()
                    ->defaultImageUrl(url('/images/placeholder-class.jpg'))
                    ->size(40),

                TextColumn::make('title')
                    ->label(__('dashboard.resources.classes.fields.title'))
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold)
                    ->limit(30)
                    ->tooltip(fn($record) => $record->title)
                    ->formatStateUsing(
                        fn($state, $record) =>
                        $record->getTranslation('title', app()->getLocale())
                    ),

                TextColumn::make('instructor.name')
                    ->label(__('dashboard.resources.classes.fields.instructor'))
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas(
                            'instructor',
                            fn($q) => $q->where('name', 'like', "%{$search}%")
                        );
                    })
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy(
                            Instructor::select('name')
                                ->whereColumn('instructors.id', 'classes.instructor_id')
                                ->limit(1),
                            $direction
                        );
                    })
                    ->icon('heroicon-o-user')
                    ->toggleable(),

                TextColumn::make('category.name')
                    ->label(__('dashboard.resources.classes.fields.category'))
                    ->badge()
                    ->color('info')
                    ->searchable()
                    ->toggleable()
                    ->formatStateUsing(fn ($state, $record) => $record->category?->getTranslation('name', app()->getLocale())),

                TextColumn::make('start_date')
                    ->label(__('dashboard.resources.classes.fields.start_date'))
                    ->date('M d, Y')
                    ->sortable()
                    ->icon('heroicon-o-calendar')
                    ->toggleable(),

                TextColumn::make('start_time')
                    ->label(__('dashboard.resources.classes.fields.start_time'))
                    ->time('H:i')
                    ->sortable()
                    ->icon('heroicon-o-clock')
                    ->toggleable(),

                TextColumn::make('total_spots')
                    ->label(__('dashboard.resources.classes.fields.total_spots'))
                    ->numeric()
                    ->sortable()
                    ->alignment('center')
                    ->toggleable(),

                IconColumn::make('is_active')
                    ->label(__('dashboard.resources.classes.fields.is_active'))
                    ->boolean()
                    ->state(fn($record) => $record->status === ClassStatusEnum::ACTIVE)
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->toggleable(),

                TextColumn::make('status')
                    ->label(__('dashboard.resources.classes.fields.status'))
                    ->badge()
                    ->color(fn(ClassStatusEnum $state): string => $state->getColor())
                    ->icon(fn(ClassStatusEnum $state): ?string => $state->getIcon())
                    ->formatStateUsing(fn(ClassStatusEnum $state): string => $state->getLabel())
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('upcoming_sessions_count')
                    ->label(__('dashboard.resources.classes.fields.upcoming_sessions'))
                    ->badge()
                    ->color('success')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label(__('dashboard.resources.classes.fields.created_at'))
                    ->dateTime('M d, Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label(__('dashboard.resources.classes.fields.updated_at'))
                    ->dateTime('M d, Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('deleted_at')
                    ->label(__('dashboard.resources.classes.fields.deleted_at'))
                    ->dateTime('M d, Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('—')
                    ->color('danger'),
            ])
            ->filters([
                TrashedFilter::make()
                    ->label(__('dashboard.filters.trashed')),

                SelectFilter::make('status')
                    ->label(__('dashboard.resources.classes.fields.status'))
                    ->options(ClassStatusEnum::options())
                    ->multiple()
                    ->searchable()
                    ->preload(),

                SelectFilter::make('instructor_id')
                    ->label(__('dashboard.resources.classes.fields.instructor'))
                    ->relationship('instructor', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple(),

                SelectFilter::make('class_category_id')
                    ->label(__('dashboard.resources.classes.fields.category'))
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple(),

                TernaryFilter::make('has_upcoming_sessions')
                    ->label(__('dashboard.resources.classes.filters.has_upcoming_sessions'))
                    ->queries(
                        true: fn(Builder $query) => $query->whereHas('sessions', function ($q) {
                            $q->where('date', '>=', now())
                                ->where('status', 'scheduled');
                        }),
                        false: fn(Builder $query) => $query->whereDoesntHave('sessions', function ($q) {
                            $q->where('date', '>=', now())
                                ->where('status', 'scheduled');
                        }),
                    ),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label(__('dashboard.actions.view'))
                    ->icon('heroicon-o-eye'),
                EditAction::make()
                    ->label(__('dashboard.actions.edit'))
                    ->icon('heroicon-o-pencil'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label(__('dashboard.actions.delete_selected'))
                        ->icon('heroicon-o-trash'),
                    ForceDeleteBulkAction::make()
                        ->label(__('dashboard.actions.force_delete_selected'))
                        ->icon('heroicon-o-trash'),
                    RestoreBulkAction::make()
                        ->label(__('dashboard.actions.restore_selected'))
                        ->icon('heroicon-o-arrow-path'),
                ]),
            ])
            ->headerActions([
                LocaleSwitcher::make(),
            ])
            ->emptyStateHeading(__('dashboard.resources.classes.empty_state.heading'))
            ->emptyStateDescription(__('dashboard.resources.classes.empty_state.description'))
            ->emptyStateIcon('heroicon-o-academic-cap')
            ->defaultSort('created_at', 'desc')
            ->poll('60s');
    }
}
