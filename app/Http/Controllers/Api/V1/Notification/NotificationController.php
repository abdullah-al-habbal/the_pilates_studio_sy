<?php

// filePath: app/Http/Controllers/Api/V1/Notification/NotificationController.php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Notification;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Notification\BulkMarkAsReadRequest;
use App\Http\Resources\Api\V1\NotificationResource;
use App\Models\AppNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{

    public function index(Request $request): JsonResponse
    {
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->paginate(20);

        return $this->success(
            NotificationResource::collection($notifications)->response()->getData(true),
        );
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $notification = $request->user()
            ->notifications()
            ->findOrFail($id);

        return $this->success(new NotificationResource($notification));
    }

    public function markAsRead(Request $request, int $id): JsonResponse
    {
        /** @var AppNotification $notification */
        $notification = $request->user()
            ->notifications()
            ->findOrFail($id);

        $notification->markAsRead();

        return $this->success(
            new NotificationResource($notification->fresh()),
            'Notification marked as read.',
        );
    }

    public function bulkMarkAsRead(BulkMarkAsReadRequest $request): JsonResponse
    {
        $request->user()
            ->notifications()
            ->whereIn('id', $request->ids)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return $this->success(null, 'Notifications marked as read.');
    }
}
