<?php

declare(strict_types=1);

namespace App\Http\Actions\Web\Admin;

use App\Handlers\Admin\HealthCheckHandler;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

final readonly class HealthCheckAction
{
    use ApiResponseTrait;

    public function __construct(
        private HealthCheckHandler $handler
    ) {}

    public function __invoke(): JsonResponse
    {
        try {
            $result = $this->handler->handle();

            $statusCode = $result['status'] === 'healthy' ? 200 : 503;

            return $this->success(
                data: $result,
                message: $result['status'] === 'healthy'
                    ? 'All systems operational'
                    : 'Some checks failed',
                status: $statusCode
            );
        } catch (\Throwable $e) {
            Log::error('Health check failed', [
                'exception' => $e,
                'message' => $e->getMessage(),
                'trace' => app()->environment('local') ? $e->getTraceAsString() : null,
            ]);

            return $this->error(
                message: 'Health check failed: ' . $e->getMessage(),
                status: 500
            );
        }
    }
}
