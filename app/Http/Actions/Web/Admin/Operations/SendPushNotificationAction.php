<?php
declare(strict_types=1);
namespace App\Http\Actions\Web\Admin\Operations;

use App\Handlers\Admin\Operations\SendPushNotificationHandler;
use App\Http\Requests\Admin\Operations\SendPushNotificationRequest;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

final readonly class SendPushNotificationAction
{
    use ApiResponseTrait;

    public function __construct(
        private SendPushNotificationHandler $handler
    ) {}

    public function __invoke(SendPushNotificationRequest $request): JsonResponse
    {
        try {
            $result = $this->handler->handle($request->toCommand());
            return $this->success($result, message: 'Notifications dispatched successfully.');
        } catch (\Throwable $e) {
            Log::error('SendPushNotificationAction failed', ['error' => $e->getMessage()]);
            return $this->error(message: 'Failed to dispatch notifications.');
        }
    }
}