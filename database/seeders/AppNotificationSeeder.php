<?php

namespace Database\Seeders;

use App\Models\AppNotification;
use App\Models\User;
use Illuminate\Database\Seeder;

class AppNotificationSeeder extends Seeder
{
    public function run(): void
    {
        $adam = User::where('email', 'adam.kim@gmail.com')->first();

        AppNotification::create([
            'user_id' => $adam->id,
            'title'   => ['en' => 'Class tomorrow',      'ar' => 'درس غداً'],
            'message' => ['en' => "Don't forget your Reformer Flow class tomorrow at 8:00 AM!", 'ar' => 'لا تنسَ درس Reformer Flow غداً في الساعة 8:00 صباحاً!'],
            'read_at' => null,
        ]);

        AppNotification::create([
            'user_id' => $adam->id,
            'title'   => ['en' => 'Booking confirmed',   'ar' => 'تم تأكيد الحجز'],
            'message' => ['en' => 'Your 12 Sessions Pack has been activated.',  'ar' => 'تم تفعيل باقة 12 جلسة.'],
            'read_at' => now(),
        ]);

        AppNotification::factory(30)->unread()->create();
        AppNotification::factory(20)->read()->create();
    }
}
