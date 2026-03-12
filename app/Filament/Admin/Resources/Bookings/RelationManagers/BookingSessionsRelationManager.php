<?php

namespace App\Filament\Admin\Resources\Bookings\RelationManagers;

use App\Enums\BookingSessionStatusEnum;
use App\Enums\ClassSessionStatusEnum;
use App\Models\ClassSession;
use Filament\Actions\{
    BulkActionGroup,
    CreateAction,
    DeleteAction,
    DeleteBulkAction,
    EditAction,
};
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class BookingSessionsRelationManager extends RelationManager
{
    protected static string $relationship = 'bookingSessions';

    protected static ?string $recordTitleAttribute = 'id';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('class_session_id')
                    ->label(__('dashboard.resources.booking_sessions.fields.class_session'))
                    ->relationship(
                        name: 'classSession',
                        titleAttribute: 'date',
                    )
                    ->modifyQueryUsing(fn (Builder $query) => $query->where('status', ClassSessionStatusEnum::SCHEDULED->value))
                    ->getOptionLabelFromRecordUsing(
                        fn (ClassSession $record) => "{$record->date} {$record->start_time}"
                    )
                    ->searchable()
                    ->preload()
                    ->required(),

                Select::make('status')
                    ->label(__('dashboard.resources.booking_sessions.fields.status'))
                    ->options(BookingSessionStatusEnum::options())
                    ->default(BookingSessionStatusEnum::RESERVED->value)
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->modifyQueryUsing(fn (Builder $query) => $query->with('classSession'))
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                TextColumn::make('classSession.date')
                    ->label(__('dashboard.resources.booking_sessions.fields.class_session'))
                    ->date('M d, Y')
                    ->sortable(),

                TextColumn::make('classSession.start_time')
                    ->label('Start')
                    ->time('H:i'),

                TextColumn::make('status')
                    ->label(__('dashboard.resources.booking_sessions.fields.status'))
                    ->badge()
                    ->color(fn(BookingSessionStatusEnum $state): string => $state->getColor())
                    ->icon(fn(BookingSessionStatusEnum $state): ?string => $state->getIcon())
                    ->formatStateUsing(fn(BookingSessionStatusEnum $state): string => $state->getLabel()),

                TextColumn::make('cancelled_at')
                    ->label(__('dashboard.resources.booking_sessions.fields.cancelled_at'))
                    ->dateTime()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label(__('dashboard.resources.booking_sessions.fields.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(BookingSessionStatusEnum::options()),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading(__('No sessions yet'))
            ->emptyStateIcon('heroicon-o-calendar');
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('dashboard.resources.booking_sessions.plural');
    }
}
