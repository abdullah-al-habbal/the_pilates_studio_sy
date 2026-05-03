<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin\Operations;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientActivitySnapshotResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'total_sessions_attended' => $this->resource->bookingSessions()
                ->where('attendance_status', 'attended')
                ->count(),

            'total_sessions_cancelled' => $this->resource->bookingSessions()
                ->where('booking_sessions.status', 'cancelled')
                ->count(),
        ];
    }
}