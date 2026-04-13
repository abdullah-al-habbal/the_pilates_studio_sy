<?php
// filePath: app/Filament/Admin/Resources/Classes/Schemas/ClassesInfolist.php

namespace App\Filament\Admin\Resources\Classes\Schemas;

use App\Enums\ClassStatusEnum;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\IconPosition;
use Filament\Support\Enums\Size;

class ClassesInfolist
{
    public static function configure(Schema $schema): Schema
    {
        $locale = app()->getLocale();

        return $schema->components([

            Grid::make(2)
                ->schema([
                    Section::make(__('dashboard.resources.classes.sections.class_overview'))
                        ->description(__('dashboard.resources.classes.sections.class_overview_desc'))
                        ->icon('heroicon-o-calendar-days')
                        ->schema([
                            TextEntry::make('title')
                                ->label(__('dashboard.resources.classes.fields.title'))
                                ->weight(FontWeight::Bold)
                                ->copyable()
                                ->copyMessage(__('dashboard.messages.copied'))
                                ->copyMessageDuration(1500)
                                ->icon('heroicon-o-clipboard')
                                ->iconPosition(IconPosition::After)
                                ->formatStateUsing(fn($state, $record) => $record->getTranslation('title', $locale))
                                ->columnSpanFull(),

                            Grid::make(2)->schema([
                                TextEntry::make('status')
                                    ->label(__('dashboard.resources.classes.fields.status'))
                                    ->badge()
                                    ->color(fn(ClassStatusEnum $state): string => $state->getColor())
                                    ->icon(fn(ClassStatusEnum $state): ?string => $state->getIcon())
                                    ->formatStateUsing(fn(ClassStatusEnum $state): string => $state->getLabel()),

                                TextEntry::make('category.name')
                                    ->label(__('dashboard.resources.classes.fields.category'))
                                    ->badge()
                                    ->color('info')
                                    ->icon('heroicon-o-tag'),

                                TextEntry::make('instructor.name')
                                    ->label(__('dashboard.resources.classes.fields.instructor'))
                                    ->url(fn($record) => $record->instructor ? route('filament.admin.resources.instructors.view', $record->instructor) : null)
                                    ->color('primary')
                                    ->icon('heroicon-o-user-circle')
                                    ->iconPosition(IconPosition::Before)
                                    ->formatStateUsing(
                                        fn($state, $record) =>
                                        $record->instructor?->getTranslation('name', $locale) ?? '—'
                                    ),

                                TextEntry::make('instructor.email')
                                    ->label(__('dashboard.resources.classes.fields.instructor_email'))
                                    ->icon('heroicon-o-envelope')
                                    ->copyable()
                                    ->copyMessage(__('dashboard.messages.copied'))
                                    ->visible(fn($record) => $record->instructor && isset($record->instructor->email)),
                            ]),
                        ]),
                ]),

            Section::make(__('dashboard.resources.classes.sections.schedule_capacity'))
                ->description(__('dashboard.resources.classes.sections.schedule_capacity_desc'))
                ->icon('heroicon-o-clock')
                ->columns(4)
                ->schema([
                    TextEntry::make('start_date')
                        ->label(__('dashboard.resources.classes.fields.start_date'))
                        ->date('M d, Y')
                        ->icon('heroicon-o-calendar')
                        ->iconPosition(IconPosition::Before),

                    TextEntry::make('end_date')
                        ->label(__('dashboard.resources.classes.fields.end_date'))
                        ->date('M d, Y')
                        ->icon('heroicon-o-calendar')
                        ->iconPosition(IconPosition::Before),

                    TextEntry::make('start_time')
                        ->label(__('dashboard.resources.classes.fields.start_time'))
                        ->time('H:i')
                        ->icon('heroicon-o-play')
                        ->iconPosition(IconPosition::Before),

                    TextEntry::make('end_time')
                        ->label(__('dashboard.resources.classes.fields.end_time'))
                        ->time('H:i')
                        ->icon('heroicon-o-stop')
                        ->iconPosition(IconPosition::Before),

                    TextEntry::make('duration_minutes')
                        ->label(__('dashboard.resources.classes.fields.duration'))
                        ->suffix(' ' . __('dashboard.resources.classes.units.minutes'))
                        ->icon('heroicon-o-arrow-path')
                        ->iconPosition(IconPosition::Before)
                        ->color('info')
                        ->badge(),

                    TextEntry::make('total_spots')
                        ->label(__('dashboard.resources.classes.fields.total_spots'))
                        ->numeric()
                        ->icon('heroicon-o-users')
                        ->iconPosition(IconPosition::Before),

                    IconEntry::make('recurrence_pattern_id')
                        ->label(__('dashboard.resources.classes.fields.recurrence_pattern'))
                        ->icon(fn($state) => $state ? 'heroicon-o-arrow-path' : 'heroicon-o-x-mark')
                        ->color(fn($state) => $state ? 'success' : 'gray')
                        ->state(
                            fn($record) =>
                            $record->recurrencePattern?->getTranslation('label', $locale) ??
                            $record->recurrencePattern?->name ??
                            __('dashboard.resources.classes.placeholders.no_recurrence')
                        ),
                ]),

            Section::make(__('dashboard.resources.classes.sections.about'))
                ->description(__('dashboard.resources.classes.sections.about_desc'))
                ->icon('heroicon-o-document-text')
                ->schema([
                    TextEntry::make('about')
                        ->label(false)
                        ->html()
                        ->prose()
                        ->formatStateUsing(fn($state, $record) => $record->getTranslation('about', $locale))
                        ->columnSpanFull()
                        ->placeholder(__('dashboard.resources.classes.placeholders.no_description')),
                ]),

            Section::make(__('dashboard.resources.classes.sections.statistics'))
                ->description(__('dashboard.resources.classes.sections.statistics_desc'))
                ->icon('heroicon-o-chart-bar')
                ->columns(3)
                ->schema([
                    TextEntry::make('total_sessions_count')
                        ->label(__('dashboard.resources.classes.fields.total_sessions'))
                        ->numeric()
                        ->badge()
                        ->color('info')
                        ->icon('heroicon-o-calendar'),

                    TextEntry::make('upcoming_sessions_count')
                        ->label(__('dashboard.resources.classes.fields.upcoming_sessions'))
                        ->numeric()
                        ->badge()
                        ->color('success')
                        ->icon('heroicon-o-arrow-right-circle'),

                    TextEntry::make('sessions_count')
                        ->label(__('dashboard.resources.classes.fields.completed_sessions'))
                        ->state(fn($record) => ($record->total_sessions_count ?? 0) - ($record->upcoming_sessions_count ?? 0))
                        ->numeric()
                        ->badge()
                        ->color('warning')
                        ->icon('heroicon-s-check-circle'),
                ]),

            Section::make(__('dashboard.resources.classes.sections.gallery'))
                ->description(__('dashboard.resources.classes.sections.gallery_desc'))
                ->icon('heroicon-o-photo')
                ->collapsible()
                ->schema([
                    RepeatableEntry::make('images')
                        ->label(false)
                        ->schema([
                            Grid::make(3)->schema([
                                ImageEntry::make('url')
                                    ->label(false)
                                    ->height(150)
                                    ->width(200)
                                    ->defaultImageUrl(url('/images/placeholder.jpg')),

                                IconEntry::make('is_primary')
                                    ->label(__('dashboard.resources.classes.fields.is_primary'))
                                    ->boolean()
                                    ->trueIcon('heroicon-o-star')
                                    ->falseIcon('heroicon-o-star')
                                    ->trueColor('warning')
                                    ->falseColor('gray'),
                            ]),
                        ])
                        ->grid(2)
                        ->placeholder(__('dashboard.resources.classes.placeholders.no_images')),
                ]),

            Section::make(__('dashboard.resources.classes.sections.metadata'))
                ->icon('heroicon-o-information-circle')
                ->collapsed()
                ->columns(3)
                ->schema([
                    TextEntry::make('id')
                        ->label(__('dashboard.resources.classes.fields.id'))
                        ->copyable()
                        ->copyMessage(__('dashboard.messages.copied'))
                        ->icon('heroicon-o-clipboard'),

                    TextEntry::make('created_at')
                        ->label(__('dashboard.resources.classes.fields.created_at'))
                        ->dateTime('M d, Y H:i')
                        ->icon('heroicon-o-calendar'),

                    TextEntry::make('updated_at')
                        ->label(__('dashboard.resources.classes.fields.updated_at'))
                        ->dateTime('M d, Y H:i')
                        ->icon('heroicon-o-arrow-path'),

                    TextEntry::make('deleted_at')
                        ->label(__('dashboard.resources.classes.fields.deleted_at'))
                        ->dateTime('M d, Y H:i')
                        ->placeholder(__('dashboard.resources.classes.placeholders.not_deleted'))
                        ->color(fn($state) => $state ? 'danger' : 'success')
                        ->icon(fn($state) => $state ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle'),
                ]),
        ]);
    }
}
