<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin\Operations;

use App\Models\BookingSession;
use App\Services\BookingSession\CancellationPolicyService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One class of a package in the "View Classes" breakdown.
 *
 * @property-read BookingSession $resource
 */
final class BookingSessionBreakdownResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $bookingSession = $this->resource;
        $classSession = $bookingSession->classSession;
        $class = $classSession?->class;
        $startsAt = $classSession?->startsAt();
        $policy = app(CancellationPolicyService::class);

        $started = $classSession !== null && $policy->hasStarted($classSession);
        $reserved = $bookingSession->isReserved();

        return [
            'id' => $bookingSession->id,
            'class_session_id' => $bookingSession->class_session_id,
            'class_title' => $class?->getTranslation('title', app()->getLocale()),
            'instructor_name' => $class?->instructor?->name,
            'date' => $classSession?->date?->toDateString(),
            'starts_at' => $startsAt?->format('g:i A'),
            'status' => $bookingSession->status->value,
            'attendance_status' => $bookingSession->attendance_status?->value,
            'can_cancel' => $reserved && $classSession !== null && ! $started && $bookingSession->attended_at === null,
            'inside_cancel_window' => $reserved && $classSession !== null && ! $started
                && $policy->isInsideWindow($classSession),
            'can_mark_attended' => (bool) $bookingSession->can_mark_attended,
            'can_mark_missed' => (bool) $bookingSession->can_mark_missed,
        ];
    }
}
