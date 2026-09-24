<?php

declare(strict_types=1);

namespace App\Services\Classes;

use App\Enums\ClassStatusEnum;
use App\Models\Classes;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

final readonly class ClassLifecycleService
{
    public function __construct(
        private ClassInputNormalizer $normalizer,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Classes
    {
        return DB::transaction(function () use ($attributes): Classes {
            $class = Classes::query()->create($this->normalizer->normalize($attributes));

            return $class->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(int $classId, array $attributes): Classes
    {
        return DB::transaction(function () use ($classId, $attributes): Classes {
            $class = $this->lockActiveRecord($classId);
            $class->fill($this->normalizer->normalize($attributes));
            $class->save();

            return $class->refresh();
        });
    }

    public function setStatus(int $classId, ClassStatusEnum $status): Classes
    {
        return DB::transaction(function () use ($classId, $status): Classes {
            $class = $this->lockActiveRecord($classId);
            $class->status = $status;
            $class->save();

            return $class->refresh();
        });
    }

    public function softDelete(int $classId): void
    {
        DB::transaction(function () use ($classId): void {
            $this->lockActiveRecord($classId)->delete();
        });
    }

    public function restore(int $classId): Classes
    {
        return DB::transaction(function () use ($classId): Classes {
            $class = Classes::withTrashed()->lockForUpdate()->findOrFail($classId);

            if ($class->trashed()) {
                $class->restore();
            }

            return $class->refresh();
        });
    }

    public function forceDelete(int $classId): void
    {
        DB::transaction(function () use ($classId): void {
            Classes::withTrashed()->lockForUpdate()->findOrFail($classId)->forceDelete();
        });
    }

    /**
     * @param  list<int>  $classIds
     */
    public function softDeleteMany(array $classIds): int
    {
        return DB::transaction(function () use ($classIds): int {
            $classes = $this->lockRecords($classIds, withTrashed: false);
            $classes->each->delete();

            return $classes->count();
        });
    }

    /**
     * @param  list<int>  $classIds
     */
    public function restoreMany(array $classIds): int
    {
        return DB::transaction(function () use ($classIds): int {
            $classes = $this->lockRecords($classIds, withTrashed: true);
            $restored = 0;

            foreach ($classes as $class) {
                if (! $class->trashed()) {
                    continue;
                }

                $class->restore();
                $restored++;
            }

            return $restored;
        });
    }

    /**
     * @param  list<int>  $classIds
     */
    public function forceDeleteMany(array $classIds): int
    {
        return DB::transaction(function () use ($classIds): int {
            $classes = $this->lockRecords($classIds, withTrashed: true);
            $classes->each->forceDelete();

            return $classes->count();
        });
    }

    private function lockActiveRecord(int $classId): Classes
    {
        return Classes::query()->lockForUpdate()->findOrFail($classId);
    }

    /**
     * @param  list<int>  $classIds
     *
     * @return Collection<int, Classes>
     */
    private function lockRecords(array $classIds, bool $withTrashed): Collection
    {
        $ids = collect($classIds)
            ->map(fn (int|string $id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->sort()
            ->values()
            ->all();

        if ($ids === []) {
            return new Collection;
        }

        $query = $withTrashed ? Classes::withTrashed() : Classes::query();

        return $query->whereKey($ids)->lockForUpdate()->get()->tap(function (Collection $classes) use ($ids): void {
            if ($classes->count() !== count($ids)) {
                $missingIds = array_values(array_diff($ids, $classes->modelKeys()));

                throw (new ModelNotFoundException)->setModel(Classes::class, $missingIds);
            }
        });
    }
}
