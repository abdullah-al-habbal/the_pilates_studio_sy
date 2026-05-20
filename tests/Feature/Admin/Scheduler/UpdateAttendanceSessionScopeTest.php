<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Scheduler;

use App\Commands\Admin\Scheduler\UpdateAttendanceCommand;
use App\Enums\AttendanceStatusEnum;
use App\Enums\BookingSessionStatusEnum;
use App\Enums\BookingStatusEnum;
use App\Enums\ClassSessionStatusEnum;
use App\Enums\UserStatusEnum;
use App\Handlers\Admin\Scheduler\UpdateAttendanceHandler;
use App\Models\Booking;
use App\Models\BookingSession;
use App\Models\ClassSession;
use App\Models\Currency;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class UpdateAttendanceSessionScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_attendance_update_rejects_booking_session_from_different_class_session(): void
    {
        $currency = Currency::factory()->create(['code' => 'USD', 'is_active' => true]);
        $package = Package::factory()->create();
        $client = User::factory()->create(['status' => UserStatusEnum::ACTIVE]);

        $booking = Booking::factory()->create([
            'user_id' => $client->id,
            'package_id' => $package->id,
            'currency_id' => $currency->id,
            'status' => BookingStatusEnum::ACTIVE,
        ]);

        $sessionA = ClassSession::factory()->create([
            'status' => ClassSessionStatusEnum::SCHEDULED,
            'date' => now()->toDateString(),
        ]);
        $sessionB = ClassSession::factory()->create([
            'status' => ClassSessionStatusEnum::SCHEDULED,
            'date' => now()->toDateString(),
        ]);

        $bookingSession = BookingSession::factory()->create([
            'booking_id' => $booking->id,
            'class_session_id' => $sessionA->id,
            'status' => BookingSessionStatusEnum::RESERVED,
        ]);

        $this->expectException(ValidationException::class);

        app(UpdateAttendanceHandler::class)->handle(
            new UpdateAttendanceCommand(
                classSessionId: $sessionB->id,
                bookingSessionId: $bookingSession->id,
                status: AttendanceStatusEnum::ATTENDED,
            )
        );
    }
}
