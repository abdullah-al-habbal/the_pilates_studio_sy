<?php

// filePath: app/Http/Controllers/Api/V1/Notification/NotificationController.php
declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Notification;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Notification\BulkMarkAsReadRequest;
use App\Http\Resources\Api\V1\NotificationResource;
use App\Models\User;
use App\Services\Notification\NotificationService;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

#[Group('Notifications')]
class NotificationController extends Controller
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {}

    #[Endpoint('List notifications', description: 'Returns a list of user notifications.')]
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $notifications = $this->notificationService->getUserNotifications(
            $user,
            $request->only(['unread'])
        );

        return $this->success(
            NotificationResource::collection($notifications)->response()->getData(true)
        );
    }

    #[Endpoint('Get notification by ID', description: 'Returns a notification by its ID.')]
    public function show(Request $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $notification = $this->notificationService->findUserNotification($user, $id);

        abort_if(! $notification, 404, 'Notification not found');

        return $this->success(new NotificationResource($notification));
    }

    #[Endpoint('Mark notification as read', description: 'Marks a notification as read.')]
    public function markAsRead(Request $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $success = $this->notificationService->markAsRead($user, $id);

        abort_if(! $success, 404, 'Notification not found or already read');

        $notification = $this->notificationService->findUserNotification($user, $id);

        return $this->success(
            new NotificationResource($notification->fresh()),
            'Notification marked as read.'
        );
    }

    #[Endpoint('Bulk mark notifications as read', description: 'Marks multiple notifications as read.')]
    public function bulkMarkAsRead(BulkMarkAsReadRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $updatedCount = $this->notificationService->bulkMarkAsRead($user, $request->ids);

        return $this->success(null, "Marked {$updatedCount} notifications as read.");
    }
}
