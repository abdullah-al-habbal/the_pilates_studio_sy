<?php

namespace App\Filament\Admin\Resources\RecurrencePatterns;

use App\Filament\Admin\Resources\RecurrencePatterns\Pages\CreateRecurrencePattern;
use App\Filament\Admin\Resources\RecurrencePatterns\Pages\EditRecurrencePattern;
use App\Filament\Admin\Resources\RecurrencePatterns\Pages\ListRecurrencePatterns;
use App\Filament\Admin\Resources\RecurrencePatterns\Pages\ViewRecurrencePattern;
use App\Filament\Admin\Resources\RecurrencePatterns\Schemas\RecurrencePatternForm;
use App\Filament\Admin\Resources\RecurrencePatterns\Schemas\RecurrencePatternInfolist;
use App\Filament\Admin\Resources\RecurrencePatterns\Tables\RecurrencePatternsTable;
use App\Models\RecurrencePattern;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class RecurrencePatternResource extends Resource
{
    protected static ?string $model = RecurrencePattern::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrow-path';

    protected static string|UnitEnum|null $navigationGroup = 'Configuration';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'label';

    public static function getRecordTitle(?Model $record): string
    {
        return $record?->name ?? $record?->title ?? ('Pattern #'.$record->id);
    }

    public static function getNavigationBadge(): ?string
    {
        return cache()->remember(
            'filament.recurrence_patterns.count',
            now()->addMinutes(5),
            fn () => static::getModel()::query()->count()
        );
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'primary';
    }

    public static function form(Schema $schema): Schema
    {
        return RecurrencePatternForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return RecurrencePatternInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RecurrencePatternsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getRecordSubNavigation(\Filament\Resources\Pages\Page $page): array
    {
        return $page->generateNavigationItems([
            ViewRecurrencePattern::class,
            EditRecurrencePattern::class,
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRecurrencePatterns::route('/'),
            'create' => CreateRecurrencePattern::route('/create'),
            'view' => ViewRecurrencePattern::route('/{record}'),
            'edit' => EditRecurrencePattern::route('/{record}/edit'),
        ];
    }
}
