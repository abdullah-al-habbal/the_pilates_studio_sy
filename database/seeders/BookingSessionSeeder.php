<?php

namespace Database\Seeders;

use App\Enums\BookingSessionStatusEnum;
use App\Enums\BookingStatusEnum;
use App\Enums\ClassSessionStatusEnum;
use App\Models\Booking;
use App\Models\BookingSession;
use App\Models\ClassSession;
use App\Models\User;
use Illuminate\Database\Seeder;

class BookingSessionSeeder extends Seeder
{
    public function run(): void
    {
        $adam        = User::where('email', 'adam.kim@gmail.com')->first();
        $adamBooking = Booking::where('user_id', $adam->id)->where('status', BookingStatusEnum::ACTIVE->value)->first();

        $this->seedAdamSessions($adamBooking);
        $this->seedRandomReserved($adam->id);
        $this->seedRandomAttended($adam->id);
    }

    private function seedAdamSessions(?Booking $adamBooking): void
    {
        if (! $adamBooking) {
            return;
        }

        ClassSession::where('status', ClassSessionStatusEnum::SCHEDULED->value)
            ->orderBy('date')
            ->limit(4)
            ->get()
            ->each(fn(ClassSession $session) => $this->insertSession(
                $adamBooking->id,
                $session->id,
                BookingSessionStatusEnum::RESERVED
            ));

        $pastSession = ClassSession::where('status', ClassSessionStatusEnum::COMPLETED->value)->orderByDesc('date')->first();

        if ($pastSession) {
            $this->insertSession($adamBooking->id, $pastSession->id, BookingSessionStatusEnum::ATTENDED);
        }
    }

    private function seedRandomReserved(int $excludeUserId): void
    {
        Booking::where('status', BookingStatusEnum::ACTIVE->value)
            ->where('user_id', '!=', $excludeUserId)
            ->limit(12)
            ->get()
            ->each(function (Booking $booking) {
                ClassSession::where('status', ClassSessionStatusEnum::SCHEDULED->value)
                    ->inRandomOrder()
                    ->limit(rand(1, 3))
                    ->get()
                    ->each(function (ClassSession $session) use ($booking) {
                        if (! $this->isAlreadyBooked($booking->user_id, $session->id)) {
                            $this->insertSession($booking->id, $session->id, BookingSessionStatusEnum::RESERVED);
                        }
                    });
            });
    }

    private function seedRandomAttended(int $excludeUserId): void
    {
        Booking::where('status', BookingStatusEnum::ACTIVE->value)
            ->where('user_id', '!=', $excludeUserId)
            ->inRandomOrder()
            ->limit(5)
            ->get()
            ->each(function (Booking $booking) {
                $session = ClassSession::where('status', ClassSessionStatusEnum::COMPLETED->value)->inRandomOrder()->first();

                if (! $session) {
                    return;
                }

                if (! $this->isAlreadyBooked($booking->user_id, $session->id)) {
                    $this->insertSession($booking->id, $session->id, BookingSessionStatusEnum::ATTENDED);
                }
            });
    }

    private function insertSession(int $bookingId, int $classSessionId, BookingSessionStatusEnum $status): void
    {
        BookingSession::withoutEvents(function () use ($bookingId, $classSessionId, $status) {
            BookingSession::firstOrCreate(
                ['booking_id' => $bookingId, 'class_session_id' => $classSessionId],
                ['status' => $status->value]
            );
        });
    }

    private function isAlreadyBooked(int $userId, int $classSessionId): bool
    {
        return BookingSession::whereHas('booking', fn($q) => $q->where('user_id', $userId))
            ->where('class_session_id', $classSessionId)
            ->whereIn('status', [
                BookingSessionStatusEnum::RESERVED->value,
                BookingSessionStatusEnum::ATTENDED->value,
            ])
            ->exists();
    }
}
