<?php
declare(strict_types=1);

namespace App\Handlers\Admin\Scheduler;

use App\Enums\AttendanceStatusEnum;
use App\Models\BookingSession;
use App\Models\ClassSession;
use App\Queries\Admin\Scheduler\GetSessionDetailsQuery;
use App\Services\Log\LoggingService;

final readonly class GetSessionDetailsHandler
{
    public function __construct(
        private readonly LoggingService $logger
    ) {
    }

    public function handle(GetSessionDetailsQuery $query): array
    {
        $this->logger->info('[Scheduler:GetSessionDetails] Fetching session details', [
            'session_id' => $query->sessionId,
        ]);

        $session = ClassSession::with([
            'class.instructor',
            'bookingSessions.booking.user',
        ])->findOrFail($query->sessionId);

        $locale = app()->getLocale();

        $title = match (true) {
            is_array($session->class?->title) => $session->class->title[$locale]
            ?? $session->class->title['en']
            ?? '[MISSING:class.title.translation]',
            !is_null($session->class?->title) => $session->class->title,
            is_null($session->class) => '[MISSING:class_session.class_relation]',
            default => '[MISSING:class.title]',
        };

        $instructor = $session->class?->instructor?->fullname
            ?? ($session->class ? '[MISSING:instructor.fullname]' : '[MISSING:class_relation→instructor]');

        $reserved = $session->bookingSessions->count();
        $capacity = (int) ($session->total_spots ?? 0);
        $fillPct = $capacity > 0
            ? min(100, (int) round($reserved / $capacity * 100))
            : 0;

        $bookings = $session->bookingSessions
            ->filter(fn(BookingSession $bs) => $bs->attendance_status !== AttendanceStatusEnum::MISSED)
            ->map(fn(BookingSession $bs) => [
                'id' => $bs->id,
                'status' => $bs->status?->value ?? '[MISSING:booking_session.status]',
                'attendance' => $bs->attendance_status?->value ?? null,
                'user' => $bs->booking?->user
                    ? [
                        'id' => $bs->booking->user->id,
                        'name' => $bs->booking->user->fullname ?? '[MISSING:user.fullname]',
                        'phone' => $bs->booking->user->phone_number ?? '[MISSING:user.phone_number]',
                        'initial' => mb_strtoupper(
                            mb_substr($bs->booking->user->fullname ?? '?', 0, 1)
                        ),
                        'credits' => $bs->booking->user->total_remaining_credits ?? 0,
                    ]
                    : null,
            ])
            ->values();

        $this->logger->info('[Scheduler:GetSessionDetails] Session details fetched', [
            'session_id' => $query->sessionId,
            'reserved' => $reserved,
            'capacity' => $capacity,
            'fill_pct' => $fillPct,
            'bookings' => $bookings->count(),
        ]);

        return [
            'id' => $session->id,
            'title' => $title,
            'instructor' => $instructor,
            'date' => $session->date?->format('M j, Y') ?? '[MISSING:session.date]',
            'start_time' => isset($session->start_time) ? substr($session->start_time, 0, 5) : '[MISSING:session.start_time]',
            'end_time' => isset($session->end_time) ? substr($session->end_time, 0, 5) : '[MISSING:session.end_time]',
            'capacity' => $capacity,
            'reserved' => $reserved,
            'fill_pct' => $fillPct,
            'available_spots' => $capacity > 0 ? max(0, $capacity - $reserved) : null,
            'is_full' => $capacity > 0 && $reserved >= $capacity,
            'bookings' => $bookings,
        ];
    }
}
