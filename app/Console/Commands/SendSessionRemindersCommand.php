<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\ClassSessionStatusEnum;
use App\Models\ClassSession;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

final class SendSessionRemindersCommand extends Command
{
    protected $signature = 'sessions:send-reminders';

    protected $description = 'Send reminders for class sessions starting within the next hour';

    public function handle(): int
    {
        $windowStart = now();
        $windowEnd = now()->addHour();

        $sessions = ClassSession::query()
            ->where('status', ClassSessionStatusEnum::SCHEDULED)
            ->whereDate('date', $windowStart->toDateString())
            ->whereBetween('start_time', [
                $windowStart->format('H:i:s'),
                $windowEnd->format('H:i:s'),
            ])
            ->withCount('bookingSessions')
            ->get();

        foreach ($sessions as $session) {
            Log::info('Session reminder queued', [
                'class_session_id' => $session->id,
                'date' => $session->date,
                'start_time' => $session->start_time,
                'reserved_count' => $session->booking_sessions_count,
            ]);
        }

        $this->info("Processed {$sessions->count()} session reminder(s).");

        return self::SUCCESS;
    }
}
