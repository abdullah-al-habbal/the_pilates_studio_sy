<?php
// filePath: app/Repositories/Eloquent/BaseEloquentRepository.php
declare(strict_types=1);

namespace App\Repositories\Eloquent;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Generic, reusable Eloquent repository.
 *
 * Subclasses declare:
 *   - model()          → the Eloquent model class string
 *   - allowedIncludes() → flat whitelist of valid dot-notation relation paths
 *   - constrainedRelations() → optional closures per relation (e.g. filters, ordering)
 *
 * The find() method handles normalization, whitelisting, and eager loading
 * automatically. No per-model duplication required.
 *
 * @template TModel of Model
 */
/**
 * Generic, reusable Eloquent repository.
 *
 * Subclasses declare:
 *   - model()          → the Eloquent model class string
 *   - allowedIncludes() → flat whitelist of valid dot-notation relation paths
 *   - constrainedRelations() → optional closures per relation (e.g. filters, ordering)
 *
 * The find() method handles normalization, whitelisting, and eager loading
 * automatically. No per-model duplication required.
 *
 * @template TModel of Model
 */
abstract class BaseEloquentRepository
{
    /**
     * The fully-qualified Eloquent model class.
     *
     * @return class-string<TModel>
     */
    abstract protected function model(): string;

    /**
     * All valid dot-notation include paths for this model.
     * Must match exactly what callers are allowed to request.
     *
     * @return string[]
     */
    abstract protected function allowedIncludes(): array;

    /**
     * Optional query constraints per relation path.
     * Keys are dot-notation paths; values are Closure(Builder): void.
     *
     * Subclasses override this to apply filters, ordering, etc.
     * Relations not listed here are loaded with a default no-op constraint.
     *
     * @return array<string, \Closure>
     */
    protected function constrainedRelations(): array
    {
        return [];
    }

    /**
     * Find a model by primary key, eager loading only allowed includes.
     *
     * @param  int|string   $id
     * @param  string[]     $includes  Raw include segments from request
     * @return TModel|null
     */
    public function find(int|string $id, array $includes = []): ?Model
    {
        $normalized = $this->normalize($includes);
        $whitelisted = $this->allowed($normalized);

        return $this->newQuery()
            ->with($this->resolveRelations($whitelisted))
            ->find($id);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Internals
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Expand each dot-notation path into all its ancestor paths so that
     * parent relations are always eager-loaded before children.
     *
     * e.g. ['classes.category'] → ['classes.category', 'classes']
     *
     * @param  string[] $includes
     * @return string[]
     */
    private function normalize(array $includes): array
    {
        $result = [];

        foreach ($includes as $include) {
            $parts = explode('.', $include);

            while (! empty($parts)) {
                $result[] = implode('.', $parts);
                array_pop($parts);
            }
        }

        return array_values(array_unique($result));
    }

    /**
     * Filter normalized includes against the declared whitelist.
     *
     * @param  string[] $includes
     * @return string[]
     */
    private function allowed(array $includes): array
    {
        return array_values(
            array_intersect($includes, $this->allowedIncludes())
        );
    }

    /**
     * Build the with() map: relation path → constraint closure.
     * Falls back to a no-op closure for relations with no constraint declared.
     *
     * @param  string[] $includes
     * @return array<string, \Closure>
     */
    private function resolveRelations(array $includes): array
    {
        $constrained = $this->constrainedRelations();
        $relations   = [];

        foreach ($includes as $path) {
            $relations[$path] = $constrained[$path] ?? static fn ($q) => $q;
        }

        return $relations;
    }

    /**
     * @return Builder<TModel>
     */
    private function newQuery(): Builder
    {
        /** @var TModel $model */
        $model = app($this->model());

        return $model->newQuery();
    }
}
