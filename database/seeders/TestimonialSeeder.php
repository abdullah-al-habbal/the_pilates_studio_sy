<?php

namespace Database\Seeders;

use App\Models\Testimonial;
use Illuminate\Database\Seeder;

class TestimonialSeeder extends Seeder
{
    public function run(): void
    {
        $testimonials = [
            [
                'name' => ['en' => 'Jessica Martinez', 'ar' => 'جيسيكا مارتينيز'],
                'role' => ['en' => 'Member since 2025', 'ar' => 'عضوة منذ 2025'],
                'quote' => ['en' => 'The app makes booking so effortless. I can see exactly which classes have spots left, reserve in seconds, and the push notifications mean I never miss a session.', 'ar' => 'التطبيق يجعل الحجز سهلاً للغاية. يمكنني رؤية الحصص المتاحة بالضبط، والحجز في ثوانٍ.']
,
                'avatar' => 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&w=100&q=80',
                'rating' => 5,
                'sort_order' => 1,
            ],
            [
                'name' => ['en' => 'Ryan Thompson', 'ar' => 'ريان طومسون'],
                'role' => ['en' => 'Member since 2024', 'ar' => 'عضو منذ 2024'],
                'quote' => ['en' => 'The Pilates Studio Syria stands out for the quality of instructors and the seamless booking experience. The credit system is genuinely flexible.', 'ar' => 'يتميز بيلاتس ستوديو سوريا بجودة المدربين وتجربة الحجز السلسة. نظام الائتمان مرن حقاً.']
,
                'avatar' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=100&q=80',
                'rating' => 5,
                'sort_order' => 2,
            ],
            [
                'name' => ['en' => 'Priya Sharma', 'ar' => 'بريا شارما'],
                'role' => ['en' => 'Member since 2025', 'ar' => 'عضوة منذ 2025'],
                'quote' => ['en' => 'The easy cancellation policy and credit refunds are a game-changer. The app is beautifully designed and I love managing everything in one place.', 'ar' => 'سياسة الإلغاء السهلة واسترداد الاعتمادات تغير قواعد اللعبة. التطبيق مصمم بشكل جميل وأحب إدارة كل شيء في مكان واحد.']
,
                'avatar' => 'https://images.unsplash.com/photo-1438761681033-6461ffad8d80?auto=format&fit=crop&w=100&q=80',
                'rating' => 5,
                'sort_order' => 3,
            ],
        ];

        foreach ($testimonials as $testimonial) {
            Testimonial::firstOrCreate(
                ['name->en' => $testimonial['name']['en']],
                $testimonial
            );
        }
    }
}
