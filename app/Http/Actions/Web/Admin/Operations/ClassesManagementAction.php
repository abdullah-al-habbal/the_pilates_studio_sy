<?php

declare(strict_types=1);

namespace App\Http\Actions\Web\Admin\Operations;

use App\Enums\ClassSessionStatusEnum;
use App\Enums\ClassStatusEnum;
use App\Enums\WeekdayEnum;
use App\Http\Requests\Admin\Operations\ClassImageUploadRequest;
use App\Http\Requests\Admin\Operations\ClassPreviewRequest;
use App\Http\Requests\Admin\Operations\ClassSessionUpsertRequest;
use App\Http\Requests\Admin\Operations\ClassUpsertRequest;
use App\Http\Resources\Admin\Operations\ClassManagementResource;
use App\Models\ClassCategory;
use App\Models\Classes;
use App\Models\ClassImage;
use App\Models\Instructor;
use App\Models\RecurrencePattern;
use App\Services\Classes\ClassInputNormalizer;
use App\Services\Classes\ClassLifecycleService;
use App\Services\Classes\ClassSchedulePreviewService;
use App\Services\Classes\ClassSessionManagementService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final readonly class ClassesManagementAction
{
    public function __construct(
        private ClassLifecycleService $lifecycle,
        private ClassInputNormalizer $normalizer,
        private ClassSchedulePreviewService $preview,
        private ClassSessionManagementService $sessions,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::enum(ClassStatusEnum::class)],
            'instructor_id' => ['nullable', 'integer'],
            'category_id' => ['nullable', 'integer'],
            'scope' => ['nullable', Rule::in(['active', 'all', 'trashed'])],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
        ]);

        $query = Classes::query()
            ->with(['instructor:id,name', 'category:id,name', 'recurrencePattern:id,name,label,interval_days,frequency_unit,frequency_interval', 'primaryImage'])
            ->withCount([
                'bookingSessions',
                'sessions as upcoming_sessions_count' => fn (Builder $query) => $query
                    ->whereDate('date', '>=', now()->toDateString())
                    ->where('status', ClassSessionStatusEnum::SCHEDULED->value),
            ]);

        if (($data['scope'] ?? 'active') === 'all') {
            $query->withTrashed();
        } elseif (($data['scope'] ?? 'active') === 'trashed') {
            $query->onlyTrashed();
        }

        $query
            ->when($data['search'] ?? null, fn (Builder $query, string $search) => $query->dashboardSearch($search))
            ->when($data['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($data['instructor_id'] ?? null, fn (Builder $query, int $id) => $query->where('instructor_id', $id))
            ->when($data['category_id'] ?? null, fn (Builder $query, int $id) => $query->where('class_category_id', $id));

        return ClassManagementResource::collection(
            $query->latest()->paginate(
                perPage: (int) ($data['per_page'] ?? 20),
                page: $data['page'] ?? null,
            ),
        )->response();
    }

    public function lookup(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(['instructor', 'category'])],
            'search' => ['nullable', 'string', 'max:255'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
        ]);

        $query = $data['type'] === 'instructor'
            ? Instructor::query()
            : ClassCategory::query();

        $query->when(
            $data['search'] ?? null,
            fn (Builder $query, string $search) => $query->where(function (Builder $nested) use ($search) {
                foreach (['en', 'ar'] as $locale) {
                    $nested->orWhereRaw(
                        'LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, ?))) LIKE ?',
                        ['$.' . $locale, '%' . mb_strtolower(addcslashes(trim($search), '\\%_')) . '%'],
                    );
                }
            }),
        );

        $paginator = $query->orderBy('name')->paginate(
            perPage: (int) ($data['per_page'] ?? 10),
            page: $data['page'] ?? null,
        );

        $locale = app()->getLocale();

        return response()->json([
            'data' => collect($paginator->items())->map(fn ($item) => [
                'id' => $item->id,
                'name' => $item->getTranslations('name'),
                'display_name' => $item->getTranslation('name', $locale, false) ?: $item->getTranslation('name', 'en'),
            ]),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function options(): JsonResponse
    {
        return response()->json(['data' => [
            'recurrence_patterns' => RecurrencePattern::query()->orderBy('id')->get()->map(fn (RecurrencePattern $pattern) => [
                'id' => $pattern->id,
                'name' => $pattern->name,
                'label' => $pattern->getTranslations('label'),
                'unit' => $pattern->resolvedFrequencyUnit()->value,
                'interval' => $pattern->resolvedFrequencyInterval(),
            ]),
            'weekdays' => collect(WeekdayEnum::cases())->map(fn (WeekdayEnum $day) => [
                'value' => $day->value,
                'label' => $day->getLabel(),
            ]),
            'statuses' => collect(ClassStatusEnum::cases())->map(fn (ClassStatusEnum $status) => [
                'value' => $status->value,
                'label' => $status->getLabel(),
            ]),
        ]]);
    }

    public function show(int $classId): ClassManagementResource
    {
        return new ClassManagementResource($this->detailQuery()->withTrashed()->findOrFail($classId));
    }

    public function detail(int $classId): View
    {
        $class = Classes::withTrashed()
            ->with([
                'instructor:id,name',
                'category:id,name',
                'recurrencePattern:id,name,label,interval_days,frequency_unit,frequency_interval',
                'images',
            ])
            ->withCount('bookingSessions')
            ->findOrFail($classId);

        $sessions = $class->sessions()
            ->withCount('bookingSessions')
            ->paginate(10)
            ->withQueryString();

        return view('admin.operations.classes.detail', compact('class', 'sessions'));
    }

    public function editPage(int $classId): View
    {
        $class = $this->detailQuery()->withTrashed()->findOrFail($classId);

        abort_if($class->trashed(), 404);

        return view('admin.operations.classes.edit', compact('class'));
    }

    public function preview(ClassPreviewRequest $request): JsonResponse
    {
        $attributes = $this->normalizer->normalize($request->validated());
        $pattern = isset($attributes['recurrence_pattern_id'])
            ? RecurrencePattern::find($attributes['recurrence_pattern_id'])
            : null;
        $instructorId = isset($attributes['instructor_id']) && $attributes['instructor_id'] !== ''
            ? (int) $attributes['instructor_id']
            : null;

        return response()->json(['data' => $this->preview->preview(
            startDate: $attributes['start_date'],
            endDate: $attributes['end_date'],
            startTime: $attributes['start_time'],
            endTime: $attributes['end_time'],
            weekdays: $attributes['weekdays'] ?? [],
            pattern: $pattern,
            instructorId: $instructorId,
            classId: null,
        )]);
    }

    public function store(ClassUpsertRequest $request): JsonResponse
    {
        return (new ClassManagementResource($this->lifecycle->create(
            $this->normalizer->normalize($request->validated()),
        )))->response()->setStatusCode(201);
    }

    public function update(ClassUpsertRequest $request, int $classId): ClassManagementResource
    {
        return new ClassManagementResource($this->lifecycle->update(
            $classId,
            $this->normalizer->normalize($request->validated()),
        ));
    }

    public function setStatus(Request $request, int $classId): ClassManagementResource
    {
        $data = $request->validate(['status' => ['required', Rule::enum(ClassStatusEnum::class)]]);

        return new ClassManagementResource(
            $this->lifecycle->setStatus($classId, ClassStatusEnum::from($data['status'])),
        );
    }

    public function destroy(int $classId): JsonResponse
    {
        $this->lifecycle->softDelete($classId);

        return response()->json(status: 204);
    }

    public function restore(int $classId): ClassManagementResource
    {
        return new ClassManagementResource($this->lifecycle->restore($classId));
    }

    public function forceDestroy(int $classId): JsonResponse
    {
        $this->lifecycle->forceDelete($classId);

        return response()->json(status: 204);
    }

    public function uploadImage(ClassImageUploadRequest $request, int $classId): JsonResponse
    {
        Classes::query()->findOrFail($classId);
        $file = $request->file('image');
        $path = $file->store('class-images/' . $classId . '/' . now()->format('Y/m/d'), 'public');

        try {
            $image = DB::transaction(fn (): ClassImage => ClassImage::query()->create([
                'class_id' => $classId,
                'url' => $path,
                'is_primary' => $request->boolean('is_primary'),
            ]));
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete($path);

            throw $exception;
        }

        return response()->json(['data' => [
            'id' => $image->id,
            'url' => $image->url,
            'image_url' => $image->image_url,
            'is_primary' => $image->is_primary,
        ]], 201);
    }

    public function deleteImage(int $classId, int $imageId): JsonResponse
    {
        $image = ClassImage::query()->where('class_id', $classId)->findOrFail($imageId);
        $image->delete();

        return response()->json(status: 204);
    }

    public function setPrimaryImage(int $classId, int $imageId): JsonResponse
    {
        $image = ClassImage::query()->where('class_id', $classId)->findOrFail($imageId);
        $image->is_primary = true;
        $image->save();

        return response()->json(['data' => [
            'id' => $image->id,
            'is_primary' => $image->is_primary,
        ]]);
    }

    public function storeSession(ClassSessionUpsertRequest $request, int $classId): JsonResponse
    {
        $session = $this->sessions->create($classId, $request->validated());

        return response()->json(['data' => ['id' => $session->id]], 201);
    }

    public function updateSession(ClassSessionUpsertRequest $request, int $classId, int $sessionId): JsonResponse
    {
        $session = $this->sessions->update($classId, $sessionId, $request->validated());

        return response()->json(['data' => ['id' => $session->id]]);
    }

    public function destroySession(int $classId, int $sessionId): JsonResponse
    {
        $this->sessions->delete($classId, $sessionId);

        return response()->json(status: 204);
    }

    private function detailQuery(): Builder
    {
        return Classes::query()
            ->with([
                'instructor:id,name',
                'category:id,name',
                'recurrencePattern:id,name,label,interval_days,frequency_unit,frequency_interval',
                'images',
                'sessions' => fn ($query) => $query->withCount('bookingSessions')->orderBy('date')->orderBy('start_time'),
            ])
            ->withCount([
                'bookingSessions',
                'sessions as upcoming_sessions_count' => fn (Builder $query) => $query
                    ->whereDate('date', '>=', now()->toDateString())
                    ->where('status', ClassSessionStatusEnum::SCHEDULED->value),
            ]);
    }
}
