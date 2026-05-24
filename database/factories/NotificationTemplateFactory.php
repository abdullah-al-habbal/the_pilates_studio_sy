<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationTemplateFactory extends Factory
{
    public function definition(): array
    {
        return [
            'key'       => 'session_reminder',
            'title'     => ['en' => 'Class Reminder', 'ar' => 'تذكير بالحصة'],
            'body'      => [
                'en' => 'Your class :class with :instructor starts at :time on :date',
                'ar' => 'حصتك :class مع :instructor تبدأ في :time بتاريخ :date',
            ],
            'data'      => null,
            'is_active' => true,
        ];
    }
}