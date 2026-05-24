<?php

namespace Database\Seeders;

use App\Models\NotificationTemplate;
use Illuminate\Database\Seeder;

class NotificationTemplateSeeder extends Seeder
{
    public function run(): void
    {
        NotificationTemplate::upsert([
            [
                'key'       => 'session_reminder',
                'title'     => json_encode(['en' => 'Class Reminder', 'ar' => 'تذكير بالحصة']),
                'body'      => json_encode([
                    'en' => 'Your class :class with :instructor starts at :time on :date',
                    'ar' => 'حصتك :class مع :instructor تبدأ في :time بتاريخ :date',
                ]),
                'data'      => null,
                'is_active' => true,
            ],
        ], ['key'], ['title', 'body', 'data', 'is_active']);
    }
}