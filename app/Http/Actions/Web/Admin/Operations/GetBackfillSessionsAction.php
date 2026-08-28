<?php

declare(strict_types=1);

namespace App\Http\Actions\Web\Admin\Operations;

use App\Handlers\Admin\Operations\GetBackfillSessionsHandler;
use App\Http\Requests\Admin\Operations\GetBackfillSessionsRequest;
use App\Http\Resources\Admin\Operations\BackfillSessionResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

final readonly class GetBackfillSessionsAction
{
    use ApiResponseTrait;

    public function __construct(
        private GetBackfillSessionsHandler $handler
    ) {}

    public function __invoke(GetBackfillSessionsRequest $request): JsonResponse
    {
        try {
            $command = $request->toCommand();
            $paginator = $this->handler->handle($command);

            return $this->success(
                data: BackfillSessionResource::collection($paginator->items()),
                message: 'Backfill sessions retrieved successfully.',
                meta: [
                    'next_cursor' => $paginator->nextCursor()?->encode(),
                    'has_more' => $paginator->hasMorePages(),
                    'window' => [
                        'from' => $command->purchasedAt->toDateString(),
                        'to' => $command->expiresAt->toDateString(),
                    ],
                ]
            );
        } catch (ValidationException $e) {
            // Surfaced as-is so the picker can show the same message the submit path would.
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Operations - GetBackfillSessions failed: '.$e->getMessage(), [
                'exception' => $e,
                'package_id' => $request->input('package_id'),
                'purchased_at' => $request->input('purchased_at'),
            ]);

            return $this->error(message: 'Failed to retrieve sessions.');
        }
    }
}
