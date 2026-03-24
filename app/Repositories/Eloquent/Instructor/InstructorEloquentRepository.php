<?php
// filePath: app/Repositories/Eloquent/Instructor/InstructorEloquentRepository.php

declare(strict_types=1);

namespace App\Repositories\Eloquent\Instructor;

use App\Enums\ClassStatusEnum;
use App\Models\Instructor;

class InstructorEloquentRepository
{
    public function find(int $id, array $includes = []): ?Instructor
    {
        $includes = $this->normalize($includes);
        $includes = $this->allowed($includes);

        return Instructor::query()
            ->with($this->relations($includes))
            ->find($id);
    }

    private function allowed(array $includes): array
    {
        $allowed = [
            'classes',
            'classes.category',
            'classes.primaryImage',
        ];

        return array_values(array_intersect($includes, $allowed));
    }

    private function normalize(array $includes): array
    {
        $result = [];

        foreach ($includes as $include) {
            $parts = explode('.', $include);

            while ($parts) {
                $result[] = implode('.', $parts);
                array_pop($parts);
            }
        }

        return array_values(array_unique($result));
    }

    private function relations(array $includes): array
    {
        $relations = [];

        if (in_array('classes', $includes)) {
            $relations['classes'] = function ($q) {
                $q->where('status', ClassStatusEnum::ACTIVE)
                  ->latest();
            };
        }

        if (in_array('classes.category', $includes)) {
            $relations['classes.category'] = fn ($q) => $q;
        }

        if (in_array('classes.primaryImage', $includes)) {
            $relations['classes.primaryImage'] = fn ($q) => $q;
        }

        return $relations;
    }
}
