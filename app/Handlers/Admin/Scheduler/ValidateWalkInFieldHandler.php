<?php

declare(strict_types=1);

namespace App\Handlers\Admin\Scheduler;

use App\Repositories\Eloquent\User\UserEloquentRepository;

final readonly class ValidateWalkInFieldHandler
{
    public function __construct(
        private UserEloquentRepository $repository
    ) {}

    /**
     * @return array{field: string, value: string, available: bool}
     */
    public function handle(string $field, string $value): array
    {
        $exists = $this->repository->existsByFieldWithoutDeleted($field, $value);

        return [
            'field' => $field,
            'value' => $value,
            'available' => ! $exists,
        ];
    }
}
