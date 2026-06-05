<?php
// filePath: app/Filament/Admin/Resources/Classes/RelationManagers/SessionsRelationManager.php

namespace App\Filament\Admin\Resources\Classes\RelationManagers;

use App\Enums\ClassSessionStatusEnum;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SessionsRelationManager extends RelationManager
{
    protected static string $relationship = 'sessions';

    protected static ?string $recordTitleAttribute = 'id';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)->schema([
                    DatePicker::make('date')
                        ->label(__('dashboard.resources.class_sessions.fields.date'))
                        ->required()
                        ->displayFormat('M d, Y')
                        ->native(false)
                        ->closeOnDateSelection(),

                    Select::make('status')
                        ->label(__('dashboard.resources.class_sessions.fields.status'))
                        ->options(ClassSessionStatusEnum::options())
                        ->default(ClassSessionStatusEnum::SCHEDULED->value)
                        ->required()
                        ->native(false),

                    TimePicker::make('start_time')
                        ->label(__('dashboard.resources.class_sessions.fields.start_time'))
                        ->required()
                        ->displayFormat('H:i')
                        ->native(false)
                        ->seconds(false),

                    TimePicker::make('end_time')
                        ->label(__('dashboard.resources.class_sessions.fields.end_time'))
                        ->required()
                        ->displayFormat('H:i')
                        ->native(false)
                        ->seconds(false)
                        ->after('start_time'),

                    TextInput::make('total_spots')
                        ->label(__('dashboard.resources.class_sessions.fields.total_spots'))
                        ->required()
                        ->numeric()
                        ->minValue(1)
                        ->default(20)
                        ->columnSpanFull(),
                ]),
            ]);
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)->schema([
                    TextEntry::make('date')
                        ->label(__('dashboard.resources.class_sessions.fields.date'))
                        ->date('M d, Y')
                        ->icon('heroicon-o-calendar'),

                    TextEntry::make('status')
                        ->label(__('dashboard.resources.class_sessions.fields.status'))
                        ->badge()
                        ->color(fn (ClassSessionStatusEnum $state): string => $state->getColor())
                        ->icon(fn (ClassSessionStatusEnum $state): ?string => $state->getIcon())
                        ->formatStateUsing(fn (ClassSessionStatusEnum $state): string => $state->getLabel()),

                    TextEntry::make('start_time')
                        ->label(__('dashboard.resources.class_sessions.fields.start_time'))
                        ->time('H:i')
                        ->icon('heroicon-o-play'),

                    TextEntry::make('end_time')
                        ->label(__('dashboard.resources.class_sessions.fields.end_time'))
                        ->time('H:i')
                        ->icon('heroicon-o-stop'),

                    TextEntry::make('total_spots')
                        ->label(__('dashboard.resources.class_sessions.fields.total_spots'))
                        ->numeric()
                        ->icon('heroicon-o-users'),

                    TextEntry::make('available_spots')
                        ->label(__('dashboard.resources.class_sessions.fields.available_spots'))
                        ->state(fn ($record) => $record->available_spots)
                        ->badge()
                        ->color(fn ($state) => $state > 0 ? 'success' : 'danger'),
                ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('date')
                    ->label(__('dashboard.resources.class_sessions.fields.date'))
                    ->date('M d, Y')
                    ->sortable(),

                TextColumn::make('start_time')
                    ->label(__('dashboard.resources.class_sessions.fields.start_time'))
                    ->time('H:i')
                    ->sortable(),

                TextColumn::make('end_time')
                    ->label(__('dashboard.resources.class_sessions.fields.end_time'))
                    ->time('H:i')
                    ->sortable(),

                TextColumn::make('total_spots')
                    ->label(__('dashboard.resources.class_sessions.fields.total_spots'))
                    ->numeric()
                    ->sortable()
                    ->alignment('center'),

                TextColumn::make('available_spots')
                    ->label(__('dashboard.resources.class_sessions.fields.available_spots'))
                    ->state(fn ($record) => $record->available_spots)
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'success' : 'danger'),

                TextColumn::make('status')
                    ->label(__('dashboard.resources.class_sessions.fields.status'))
                    ->badge()
                    ->color(fn (ClassSessionStatusEnum $state): string => $state->getColor())
                    ->icon(fn (ClassSessionStatusEnum $state): ?string => $state->getIcon())
                    ->formatStateUsing(fn (ClassSessionStatusEnum $state): string => $state->getLabel())
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('dashboard.resources.class_sessions.fields.status'))
                    ->options(ClassSessionStatusEnum::options())
                    ->multiple()
                    ->searchable()
                    ->preload(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label(__('dashboard.actions.add_session'))
                    ->modalWidth('lg'),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label(__('dashboard.actions.view')),
                EditAction::make()
                    ->label(__('dashboard.actions.edit')),
                DeleteAction::make()
                    ->label(__('dashboard.actions.delete'))
                    ->modalHeading(__('dashboard.actions.delete_session'))
                    ->modalDescription(__('dashboard.actions.delete_session_confirmation')),
            ])
            ->bulkActions([])
            ->emptyStateHeading(__('dashboard.resources.class_sessions.empty_state.heading'))
            ->emptyStateDescription(__('dashboard.resources.class_sessions.empty_state.description'))
            ->emptyStateIcon('heroicon-o-calendar')
            ->defaultSort('date', 'asc');
    }
}
