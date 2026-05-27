<?php

declare(strict_types=1);

namespace App\Http\Actions\Web\Admin\Operations;

use App\Handlers\Admin\Operations\CreatePackageHandler;
use App\Http\Requests\Admin\Operations\CreatePackageRequest;
use Illuminate\Http\JsonResponse;

final readonly class CreatePackageAction
{
    public function __construct(
        private CreatePackageHandler $handler
    ) {}

    public function __invoke(CreatePackageRequest $request): JsonResponse
    {
        $package = $this->handler->handle(
            name: $request->input('name'),
            totalCredits: (int) $request->input('total_credits'),
            validityDays: $request->input('validity_days'),
            amount: (int) $request->input('amount'),
        );

        return response()->json([
            'success' => true,
            'data' => $package,
        ]);
    }
}
