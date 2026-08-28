<?php

// filePath: app/Filament/Admin/Resources/Classes/Schemas/ClassesForm.php

namespace App\Filament\Admin\Resources\Classes\Schemas;

use App\Enums\ClassStatusEnum;
use App\Enums\WeekdayEnum;
use App\Models\ClassCategory;
use App\Models\Classes;
use App\Models\Instructor;
use App\Models\RecurrencePattern;
use App\Services\Validation\ClassScheduleValidationService;
use Closure;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Validation\ValidationException;

class ClassesForm
{
    public const MODE_WEEKDAYS = 'weekdays';

    public const MODE_INTERVAL = 'interval';

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
                                            $instructor->id => $instructor->getTranslation('name', $locale),
                                        ]);
                                })
                                ->searchable()
                                ->preload()
                                ->required()
                                ->loadingMessage(__('dashboard.messages.loading'))
                                ->searchPrompt(__('dashboard.messages.search_prompt'))
                                // Instructors are a small lookup an admin often has to add
                                // mid-flow. The dedicated InstructorForm cannot be reused
                                // here: it relies on the resource's locale switcher, which a
                                // modal has no access to, so the locales are asked for
                                // explicitly instead.
                                ->createOptionForm([
                                    TextInput::make('name.en')
                                        ->label(__('dashboard.resources.classes.fields.instructor_name_en'))
                                        ->required()
                                        ->maxLength(255),

                                    TextInput::make('name.ar')
                                        ->label(__('dashboard.resources.classes.fields.instructor_name_ar'))
                                        ->maxLength(255)
                                        ->helperText(__('dashboard.resources.classes.helpers.instructor_name_ar')),
                                ])
                                ->createOptionAction(
                                    fn ($action) => $action
                                        ->modalHeading(__('dashboard.resources.classes.actions.create_instructor'))
                                        ->modalWidth('md'),
                                )
                                ->createOptionUsing(fn (array $data): int => self::createInstructor($data))
                                ->helperText(__('dashboard.resources.classes.helpers.instructor'))
                                ->columnSpan(1),

                            Select::make('class_category_id')
                                ->label(__('dashboard.resources.classes.fields.category'))
                                ->options(function () use ($locale) {
                                    return ClassCategory::query()
                                        ->select('id', 'name')
                                        ->get()
                                        ->mapWithKeys(fn (ClassCategory $category) => [
                                            $category->id => $category->getTranslation('name', $locale),
                                        ]);
                                })
                                ->searchable()
                                ->preload()
                                ->required()
                                ->helperText(__('dashboard.resources.classes.helpers.category'))
                                ->columnSpan(1),

                            TextInput::make('title')
                                ->label(__('dashboard.resources.classes.fields.title'))
                                ->required()
                                ->maxLength(255)
                                ->helperText(__('dashboard.resources.classes.helpers.title'))
                                ->columnSpanFull(),
                        ]),

                        RichEditor::make('about')
                            ->label(__('dashboard.resources.classes.fields.about'))
                            ->required()
                            ->columnSpanFull()
                            ->helperText(__('dashboard.resources.classes.helpers.about')),
                    ]),

                Placeholder::make('money_lock_warning')
                    ->label('')
                    ->content(fn (?Classes $record) => 'This class has customer bookings. Changing dates, times, or recurrence would violate paid reservations. To offer different dates, create a new class.')
                    ->visible(fn (?Classes $record) => $record?->exists && $record->hasBookedSessions())
                    ->columnSpanFull(),

                Section::make(__('dashboard.resources.classes.sections.schedule'))
                    ->description(__('dashboard.resources.classes.sections.schedule_desc'))
                    ->icon('heroicon-o-calendar')
                    ->schema([
                        ToggleButtons::make('schedule_mode')
                            ->label(__('dashboard.resources.classes.fields.schedule_mode'))
                            ->options([
                                self::MODE_WEEKDAYS => __('dashboard.resources.classes.fields.weekdays'),
                                self::MODE_INTERVAL => __('dashboard.resources.classes.fields.recurrence_pattern'),
                            ])
                            ->icons([
                                self::MODE_WEEKDAYS => 'heroicon-o-calendar-days',
                                self::MODE_INTERVAL => 'heroicon-o-arrow-path',
                            ])
                            ->inline()
                            ->required()
                            ->live()
                            // Form-only field: the mode is implied by which of the
                            // two columns ends up set, so it is never persisted.
                            ->dehydrated(false)
                            ->default(self::MODE_WEEKDAYS)
                            ->afterStateHydrated(function (ToggleButtons $component, ?Classes $record): void {
                                if ($record?->exists) {
                                    $component->state(
                                        $record->hasWeekdaySchedule() ? self::MODE_WEEKDAYS : self::MODE_INTERVAL,
                                    );
                                }
                            })
                            ->disabled(fn (?Classes $record) => $record?->exists && $record->hasBookedSessions())
                            ->helperText(__('dashboard.resources.classes.helpers.schedule_mode'))
                            ->columnSpanFull(),

                        CheckboxList::make('weekdays')
                            ->label(__('dashboard.resources.classes.fields.weekdays'))
                            ->options(WeekdayEnum::options())
                            ->columns(4)
                            ->bulkToggleable()
                            ->live()
                            ->rule(self::scheduleWindowRule())
                            ->visible(fn (Get $get) => $get('schedule_mode') === self::MODE_WEEKDAYS)
                            ->required(fn (Get $get) => $get('schedule_mode') === self::MODE_WEEKDAYS)
                            ->disabled(fn (?Classes $record) => $record?->exists && $record->hasBookedSessions())
                            ->helperText(__('dashboard.resources.classes.helpers.weekdays'))
                            ->columnSpanFull(),

                        Select::make('recurrence_pattern_id')
                            ->label(__('dashboard.resources.classes.fields.recurrence_pattern'))
                            ->options(function () use ($locale) {
                                return RecurrencePattern::query()
                                    ->select('id', 'name', 'label')
                                    ->get()
                                    ->mapWithKeys(fn (RecurrencePattern $pattern) => [
                                        $pattern->id => $pattern->getTranslation('label', $locale) ?: $pattern->name,
                                    ]);
                            })
                            ->searchable()
                            ->live()
                            ->rule(self::scheduleWindowRule())
                            ->visible(fn (Get $get) => $get('schedule_mode') === self::MODE_INTERVAL)
                            ->required(fn (Get $get) => $get('schedule_mode') === self::MODE_INTERVAL)
                            ->disabled(fn (?Classes $record) => $record?->exists && $record->hasBookedSessions())
                            ->helperText(__('dashboard.resources.classes.helpers.recurrence_pattern'))
                            ->columnSpanFull(),

                        Grid::make(3)->schema([
                            DatePicker::make('start_date')
                                ->label(__('dashboard.resources.classes.fields.start_date'))
                                ->required()
                                ->live()
                                ->rule(self::scheduleWindowRule())
                                ->displayFormat('M d, Y')
                                ->native(false)
                                ->closeOnDateSelection()
                                ->disabled(fn (?Classes $record) => $record?->exists && $record->hasBookedSessions())
                                ->helperText(__('dashboard.resources.classes.helpers.start_date'))
                                ->columnSpan(1),

                            DatePicker::make('end_date')
                                ->label(__('dashboard.resources.classes.fields.end_date'))
                                ->required()
                                ->live()
                                ->rule(self::scheduleWindowRule())
                                ->displayFormat('M d, Y')
                                ->native(false)
                                ->closeOnDateSelection()
                                ->afterOrEqual('start_date')
                                ->disabled(fn (?Classes $record) => $record?->exists && $record->hasBookedSessions())
                                ->helperText(__('dashboard.resources.classes.helpers.end_date'))
                                ->columnSpan(1),

                            Grid::make(2)->schema([
                                TimePicker::make('start_time')
                                    ->label(__('dashboard.resources.classes.fields.start_time'))
                                    ->required()
                                    ->live()
                                    ->rule(self::scheduleWindowRule())
                                    ->displayFormat('H:i')
                                    ->native(false)
                                    ->seconds(false)
                                    ->disabled(fn (?Classes $record) => $record?->exists && $record->hasBookedSessions())
                                    ->helperText(__('dashboard.resources.classes.helpers.start_time'))
                                    ->columnSpan(1),

                                TimePicker::make('end_time')
                                    ->label(__('dashboard.resources.classes.fields.end_time'))
                                    ->required()
                                    ->live()
                                    ->rule(self::scheduleWindowRule())
                                    ->displayFormat('H:i')
                                    ->native(false)
                                    ->seconds(false)
                                    ->after('start_time')
                                    ->disabled(fn (?Classes $record) => $record?->exists && $record->hasBookedSessions())
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
                                ->helperText(
                                    fn (?Classes $record) => $record?->exists && $record->hasBookedSessions()
                                    ? __('Reducing capacity below current reservations is blocked by the system.')
                                    : __(key: 'dashboard.resources.classes.helpers.total_spots'),
                                )
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
                        Repeater::make('images')
                            ->label(__('dashboard.resources.classes.fields.additional_images'))
                            ->relationship('images')
                            ->schema([
                                FileUpload::make('url')
                                    ->label(__('dashboard.resources.classes.fields.image'))
                                    ->image()
                                    ->imageEditor()
                                    ->disk('public')
                                    ->maxSize(5120)
                                    ->directory('classes/gallery')
                                    ->visibility('public')
                                    ->required(fn (string $operation) => $operation === 'create')
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

    /**
     * Persist an instructor created from the Select's modal.
     *
     * `name` is a translatable json column, so a blank Arabic name would be
     * stored as an explicit null and render an empty option label in the
     * Arabic panel. Falling back to the English name mirrors InstructorFactory
     * and keeps every option labelled in both locales.
     *
     * @param  array<string, mixed>  $data
     */
    public static function createInstructor(array $data): int
    {
        $en = trim((string) ($data['name']['en'] ?? ''));
        $ar = trim((string) ($data['name']['ar'] ?? ''));

        return Instructor::create([
            'name' => ['en' => $en, 'ar' => $ar !== '' ? $ar : $en],
        ])->id;
    }

    /**
     * Force exactly one scheduling mode into the payload.
     *
     * Hidden Filament components are not dehydrated, so on edit the field for
     * the mode that is *not* selected never reaches $data — which would leave a
     * stale value in the database and produce a class with both modes set.
     * Nulling the unused column explicitly is what makes mode switching work.
     *
     * @param  array<string, mixed>  $data
     *
     * @return array<string, mixed>
     */
    public static function normaliseScheduleMode(array $data): array
    {
        unset($data['schedule_mode']);

        $weekdays = $data['weekdays'] ?? null;

        if (is_array($weekdays) && $weekdays !== []) {
            $data['weekdays'] = array_values($weekdays);
            $data['recurrence_pattern_id'] = null;

            return $data;
        }

        $data['weekdays'] = null;

        return $data;
    }

    /**
     * Live window validation, shared by every field that can invalidate it.
     *
     * In weekday mode there is no interval, so the minimum-span rule is skipped
     * and only the start/end ordering is checked.
     */
    private static function scheduleWindowRule(): Closure
    {
        return function (Get $get): Closure {
            return function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                $isWeekdayMode = $get('schedule_mode') === self::MODE_WEEKDAYS;

                $intervalDays = null;

                if (! $isWeekdayMode) {
                    $pattern = RecurrencePattern::find((int) $get('recurrence_pattern_id'));

                    if (! $pattern) {
                        return;
                    }

                    $intervalDays = $pattern->interval_days;
                }

                $validator = app(ClassScheduleValidationService::class);

                try {
                    $validator->assertValidTimes($get('start_time'), $get('end_time'));

                    $validator->assertValidWindow(
                        $get('start_date'),
                        $get('end_date'),
                        $intervalDays,
                    );
                } catch (ValidationException $exception) {
                    $fail(collect($exception->errors())->flatten()->first());
                }
            };
        };
    }
}
