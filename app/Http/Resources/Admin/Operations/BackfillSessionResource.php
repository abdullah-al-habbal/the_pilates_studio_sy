<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin\Operations;

use App\Models\ClassSession;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/**
 * One selectable past session in the historical backfill picker.
 *
 * @property-read ClassSession $resource
 */
final class BackfillSessionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $session = $this->resource;
        $class = $session->class;

        return [
            'id' => $session->id,
            'date' => $session->date->toDateString(),
            'start_time' => Carbon::parse($session->start_time)->format('g:i A'),
            'end_time' => Carbon::parse($session->end_time)->format('g:i A'),
            'class_title' => $class?->getTranslation('title', app()->getLocale()),
            'instructor_name' => $class?->instructor?->name,
            'total_spots' => $session->total_spots,
            // Context only. Capacity does not block a backfill — the class already happened, so a
            // full session is a historical fact rather than a reason to refuse the record.
            'reserved_count' => (int) ($session->booking_sessions_count ?? 0),
        ];
    }
}
