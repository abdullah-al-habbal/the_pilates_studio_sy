<?php
declare(strict_types=1);

namespace App\Handlers\Admin\Scheduler;

use App\Models\BookingSession;
use App\Models\ClassSession;
use App\Queries\Admin\Scheduler\GetSessionDetailsQuery;

final readonly class GetSessionDetailsHandler
{
    public function handle(GetSessionDetailsQuery $query): array
    {
        $session = ClassSession::with([
            'class.instructor',
            'bookingSessions.booking.user',
        ])->findOrFail($query->sessionId);

        $locale   = app()->getLocale();
        $title    = is_array($session->class?->title)
            ? ($session->class->title[$locale] ?? $session->class->title['en'] ?? '—')
            : ($session->class?->title ?? '—');
        $reserved = $session->bookingSessions->count();
        $capacity = (int) ($session->total_spots ?? 0);

        $bookings = $session->bookingSessions->map(fn (BookingSession $bs) => [
            'id'         => $bs->id,
            'status'     => $bs->status?->value,
            'attendance' => $bs->attendance_status?->value,
            'user'       => $bs->booking?->user ? [
                'id'      => $bs->booking->user->id,
                'name'    => $bs->booking->user->fullname,
                'phone'   => $bs->booking->user->phone_number,
                'initial' => mb_strtoupper(mb_substr($bs->booking->user->fullname, 0, 1)),
                'credits' => $bs->booking->user->total_remaining_credits,
            ] : null,
        ]);

        return [
            'id'              => $session->id,
            'title'           => $title,
            'instructor'      => $session->class?->instructor?->fullname ?? '—',
            'date'            => $session->date?->format('M j, Y'),
            'start_time'      => substr($session->start_time, 0, 5),
            'end_time'        => substr($session->end_time, 0, 5),
            'capacity'        => $capacity,
            'reserved'        => $reserved,
            'available_spots' => $capacity > 0 ? max(0, $capacity - $reserved) : null,
            'is_full'         => $capacity > 0 && $reserved >= $capacity,
            'bookings'        => $bookings,
        ];
    }
}
