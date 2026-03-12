<?php

namespace Database\Seeders;

use App\Models\Language;
use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Database\Seeder;

class UserSettingSeeder extends Seeder
{
    public function run(): void
    {
        $english = Language::where('code', 'en')->first();
        $arabic  = Language::where('code', 'ar')->first();

        $admin = User::where('email', 'admin@studio.com')->first();
        UserSetting::firstOrCreate(
            ['user_id' => $admin->id],
            [
                'preferred_language_id' => $english->id,
                'allow_notifications'   => true,
                'fcm_token'             => null,
            ]
        );

        User::whereDoesntHave('settings')->get()->each(function (User $user) use ($english, $arabic) {
            UserSetting::create([
                'user_id'               => $user->id,
                'preferred_language_id' => rand(0, 1) ? $english->id : $arabic->id,
                'allow_notifications'   => rand(0, 1),
                'fcm_token'             => null,
            ]);
        });
    }
}
