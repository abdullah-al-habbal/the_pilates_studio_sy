<?php

declare(strict_types=1);

namespace App\Http\Actions\Web\Admin\Operations;

use App\Handlers\Admin\Operations\CreateClientHandler;
use App\Http\Requests\Admin\Operations\CreateClientRequest;
use App\Http\Resources\Admin\Operations\ClientOptionResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

final readonly class CreateClientAction
{
    use ApiResponseTrait;

    public function __construct(
        private CreateClientHandler $handler,
    ) {}

    public function __invoke(CreateClientRequest $request): JsonResponse
    {
        try {
            $client = $this->handler->handle(
                fullname: $request->string('fullname')->toString(),
                phoneNumber: $request->string('phone_number')->toString(),
                email: $request->filled('email') ? $request->string('email')->toString() : null,
                dateOfBirth: $request->filled('date_of_birth')
                    ? $request->string('date_of_birth')->toString()
                    : null,
                password: $request->password(),
            );

            Log::info('Operations - Client created', [
                'admin_id' => auth()->id(),
                'client_id' => $client->id,
            ]);

            return $this->created(
                data: new ClientOptionResource($client),
                message: 'Client created successfully.',
            );
        } catch (\Throwable $e) {
            Log::error('Operations - CreateClient failed: ' . $e->getMessage(), [
                'exception' => $e,
                'phone_number' => $request->input('phone_number'),
            ]);

            return $this->unprocessable($e->getMessage());
        }
    }
}
