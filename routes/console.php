<?php

// filePath: routes/console.php
use App\Console\Commands\Send24HourSessionReminders;
use Illuminate\Support\Facades\Schedule;

Schedule::command('sessions:send-reminders')->everyFiveMinutes();
Schedule::command(Send24HourSessionReminders::class)->dailyAt('08:00');
Schedule::command('logs:cleanup')->dailyAt('03:00');
