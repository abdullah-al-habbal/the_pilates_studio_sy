<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\NotificationTemplates;

use App\Filament\Admin\Resources\NotificationTemplates\Pages\CreateNotificationTemplate;
use App\Filament\Admin\Resources\NotificationTemplates\Pages\EditNotificationTemplate;
use App\Filament\Admin\Resources\NotificationTemplates\Pages\ListNotificationTemplates;
use App\Filament\Admin\Resources\NotificationTemplates\Pages\ViewNotificationTemplate;
use App\Models\NotificationTemplate;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class NotificationTemplateResource extends Resource
{
    protected static ?string $model = NotificationTemplate::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-square-3-stack-3d';

    protected static string|UnitEnum|null $navigationGroup = 'Configuration';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'key';

    public static function getRecordTitle(?Model $record): string
    {
        return $record?->key ?? 'Notification Template';
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::query()->count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'primary';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make('key')
                    ->label('Template Key')
                    ->unique(ignoreRecord: true)
                    ->required()
                    ->helperText('e.g., session_reminder, refund_notification'),

                Tabs::make('Translations')
                    ->tabs([
                        Tab::make('English')
                            ->schema([
                                TextInput::make('title_en')
                                    ->label('Title (English)')
                                    ->default(fn($record) => $record?->getTranslation('title', 'en'))
                                    ->required(),
                                Textarea::make('body_en')
                                    ->label('Body (English)')
                                    ->default(fn($record) => $record?->getTranslation('body', 'en'))
                                    ->required()
                                    ->helperText('Use :class, :instructor, :time, :date for placeholders'),
                            ]),
                        Tab::make('Arabic')
                            ->schema([
                                TextInput::make('title_ar')
                                    ->label('Title (Arabic)')
                                    ->default(fn($record) => $record?->getTranslation('title', 'ar'))
                                    ->required(),
                                Textarea::make('body_ar')
                                    ->label('Body (Arabic)')
                                    ->default(fn($record) => $record?->getTranslation('body', 'ar'))
                                    ->required()
                                    ->helperText('Use :class, :instructor, :time, :date for placeholders'),
                            ]),
                    ]),

                Toggle::make('is_active')
                    ->label('Is Active')
                    ->default(true),

                KeyValue::make('data')
                    ->label('Extra Data (Optional)')
                    ->nullable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('key')
                    ->label('Key')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('title')
                    ->label('Title')
                    ->getStateUsing(fn($record) => $record->getResolvedTitle('en')),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M d, Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Is Active'),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNotificationTemplates::route('/'),
            'create' => CreateNotificationTemplate::route('/create'),
            'view' => ViewNotificationTemplate::route('/{record}'),
            'edit' => EditNotificationTemplate::route('/{record}/edit'),
        ];
    }
}
