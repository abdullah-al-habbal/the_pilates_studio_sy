<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\ClassSessionStatusEnum;
use App\Models\ClassSession;
use App\Notifications\SessionReminderNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

final class Send24HourSessionReminders extends Command
{
    protected $signature = 'sessions:remind-24h';
    protected $description = 'Send push notifications 24 hours before class sessions start';

    public function handle(): int
    {
        Log::info('24-hour session reminder command started');

        $target = now()->addDay()->startOfDay();

        $sessions = ClassSession::query()
            ->where('status', ClassSessionStatusEnum::SCHEDULED)
            ->whereDate('date', $target->toDateString())
            ->with(['class.instructor', 'bookingSessions.user.settings'])
            ->get();

        foreach ($sessions as $session) {
            foreach ($session->bookingSessions as $booking) {
                $user = $booking->user;

                try {
                    if (! $user) {
                        Log::warning('Booking has no user attached', [
                            'session_id' => $session->id,
                            'booking_id' => $booking->id,
                        ]);
                        continue;
                    }

                    if (! $user->settings?->allow_notifications) {
                        Log::info('Notifications disabled for user', [
                            'user_id' => $user->id,
                        ]);
                        continue;
                    }

                    if (! $user->fcm_token) {
                        Log::warning('User missing FCM token', [
                            'user_id' => $user->id,
                        ]);
                        continue;
                    }

                    $user->notify(new SessionReminderNotification($session));

                    Log::info('24-hour reminder notification dispatched', [
                        'user_id' => $user->id,
                        'session_id' => $session->id,
                    ]);
                } catch (Throwable $exception) {
                    Log::error('Failed sending 24-hour session reminder', [
                        'user_id' => $user?->id,
                        'session_id' => $session->id,
                        'error' => $exception->getMessage(),
                        'trace' => $exception->getTraceAsString(),
                    ]);
                }
            }
        }

        $this->info("Reminders sent for {$sessions->count()} sessions.");
        return self::SUCCESS;
    }
}