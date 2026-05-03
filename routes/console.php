<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('sessions:send-reminders')->everyFiveMinutes();
