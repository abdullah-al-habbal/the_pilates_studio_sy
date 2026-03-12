<?php
// filePath: app/Filament/Admin/Resources/Classes/Schemas/ClassesForm.php

namespace App\Filament\Admin\Resources\Classes\Schemas;

use App\Enums\ClassStatusEnum;
use App\Models\ClassCategory;
use App\Models\Instructor;
use App\Models\RecurrencePattern;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use LaraZeus\SpatieTranslatable\Forms\Components\MultiLang;

class ClassesForm
{
    public static function configure(Schema $schema): Schema
    {
        $locale = app()->getLocale();

        return $schema
            ->components([
                Section::make(__('dashboard.resources.classes.sections.basic_info'))
                    ->description(__('dashboard.resources.classes.sections.basic_info_desc'))
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('instructor_id')
                                ->label(__('dashboard.resources.classes.fields.instructor'))
                                ->options(function () use ($locale) {
                                    return Instructor::query()
                                        ->select('id', 'name')
                                        ->get()
                                        ->mapWithKeys(fn (Instructor $instructor) => [
                                            $instructor->id => $instructor->getTranslation('name', $locale)
                                        ]);
                                })
                                ->searchable()
                                ->preload()
                                ->required()
                                ->loadingMessage(__('dashboard.messages.loading'))
                                ->searchPrompt(__('dashboard.messages.search_prompt'))
                                ->helperText(__('dashboard.resources.classes.helpers.instructor'))
                                ->columnSpan(1),

                            Select::make('class_category_id')
                                ->label(__('dashboard.resources.classes.fields.category'))
                                ->options(function () use ($locale) {
                                    return ClassCategory::query()
                                        ->select('id', 'name')
                                        ->get()
                                        ->mapWithKeys(fn (ClassCategory $category) => [
                                            $category->id => $category->getTranslation('name', $locale)
                                        ]);
                                })
                                ->searchable()
                                ->preload()
                                ->required()
                                ->helperText(__('dashboard.resources.classes.helpers.category'))
                                ->columnSpan(1),

                            MultiLang::make('title')
                                ->label(__('dashboard.resources.classes.fields.title'))
                                ->required()
                                ->maxLength(255)
                                ->helperText(__('dashboard.resources.classes.helpers.title'))
                                ->columnSpan(1),

                            Select::make('recurrence_pattern_id')
                                ->label(__('dashboard.resources.classes.fields.recurrence_pattern'))
                                ->options(function () use ($locale) {
                                    return RecurrencePattern::query()
                                        ->select('id', 'name', 'label')
                                        ->get()
                                        ->mapWithKeys(fn (RecurrencePattern $pattern) => [
                                            $pattern->id => $pattern->getTranslation('label', $locale) ?: $pattern->name
                                        ]);
                                })
                                ->searchable()
                                ->nullable()
                                ->helperText(__('dashboard.resources.classes.helpers.recurrence_pattern'))
                                ->columnSpan(1),
                        ]),

                        MultiLang::make('about')
                            ->label(__('dashboard.resources.classes.fields.about'))
                            ->required()
                            ->field('rich')
                            ->columnSpanFull()
                            ->helperText(__('dashboard.resources.classes.helpers.about')),
                    ]),

                Section::make(__('dashboard.resources.classes.sections.schedule'))
                    ->description(__('dashboard.resources.classes.sections.schedule_desc'))
                    ->icon('heroicon-o-calendar')
                    ->schema([
                        Grid::make(3)->schema([
                            DatePicker::make('start_date')
                                ->label(__('dashboard.resources.classes.fields.start_date'))
                                ->required()
                                ->displayFormat('M d, Y')
                                ->native(false)
                                ->closeOnDateSelection()
                                ->helperText(__('dashboard.resources.classes.helpers.start_date'))
                                ->columnSpan(1),

                            DatePicker::make('end_date')
                                ->label(__('dashboard.resources.classes.fields.end_date'))
                                ->required()
                                ->displayFormat('M d, Y')
                                ->native(false)
                                ->closeOnDateSelection()
                                ->afterOrEqual('start_date')
                                ->helperText(__('dashboard.resources.classes.helpers.end_date'))
                                ->columnSpan(1),

                            Grid::make(2)->schema([
                                TimePicker::make('start_time')
                                    ->label(__('dashboard.resources.classes.fields.start_time'))
                                    ->required()
                                    ->displayFormat('H:i')
                                    ->native(false)
                                    ->seconds(false)
                                    ->helperText(__('dashboard.resources.classes.helpers.start_time'))
                                    ->columnSpan(1),

                                TimePicker::make('end_time')
                                    ->label(__('dashboard.resources.classes.fields.end_time'))
                                    ->required()
                                    ->displayFormat('H:i')
                                    ->native(false)
                                    ->seconds(false)
                                    ->after('start_time')
                                    ->helperText(__('dashboard.resources.classes.helpers.end_time'))
                                    ->columnSpan(1),
                            ])->columnSpan(1),
                        ]),
                    ]),

                Section::make(__('dashboard.resources.classes.sections.capacity'))
                    ->description(__('dashboard.resources.classes.sections.capacity_desc'))
                    ->icon('heroicon-o-users')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('total_spots')
                                ->label(__('dashboard.resources.classes.fields.total_spots'))
                                ->required()
                                ->numeric()
                                ->minValue(1)
                                ->maxValue(999)
                                ->default(20)
                                ->helperText(__('dashboard.resources.classes.helpers.total_spots'))
                                ->suffix('spots')
                                ->columnSpan(1),

                            Select::make('status')
                                ->label(__('dashboard.resources.classes.fields.status'))
                                ->options(ClassStatusEnum::options())
                                ->default(ClassStatusEnum::ACTIVE->value)
                                ->required()
                                ->native(false)
                                ->helperText(__('dashboard.resources.classes.helpers.status'))
                                ->columnSpan(1),
                        ]),
                    ]),

                Section::make(__('dashboard.resources.classes.sections.images'))
                    ->description(__('dashboard.resources.classes.sections.images_desc'))
                    ->icon('heroicon-o-photo')
                    ->collapsible()
                    ->schema([
                        FileUpload::make('image')
                            ->label(__('dashboard.resources.classes.fields.primary_image'))
                            ->image()
                            ->imageEditor()
                            ->imageEditorAspectRatios([
                                '16:9',
                                '4:3',
                                '1:1',
                            ])
                            ->maxSize(5120)
                            ->maxFiles(1)
                            ->directory('classes')
                            ->visibility('public')
                            ->helperText(__('dashboard.resources.classes.helpers.primary_image'))
                            ->columnSpanFull(),

                        Repeater::make('images')
                            ->label(__('dashboard.resources.classes.fields.additional_images'))
                            ->relationship('images')
                            ->schema([
                                FileUpload::make('image_path')
                                    ->label(__('dashboard.resources.classes.fields.image'))
                                    ->image()
                                    ->imageEditor()
                                    ->maxSize(5120)
                                    ->directory('classes/gallery')
                                    ->visibility('public')
                                    ->required()
                                    ->columnSpan(2),

                                Toggle::make('is_primary')
                                    ->label(__('dashboard.resources.classes.fields.is_primary'))
                                    ->default(false)
                                    ->inline(false)
                                    ->columnSpan(1),
                            ])
                            ->columns(3)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['is_primary'] ? 'Primary Image' : null)
                            ->addActionLabel(__('dashboard.resources.classes.actions.add_image'))
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
