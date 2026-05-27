<?php

namespace Database\Seeders;

use App\Models\Instructor;
use Illuminate\Database\Seeder;

class InstructorSeeder extends Seeder
{
    public function run(): void
    {
        $instructors = [
            [
                'name' => ['en' => 'Sarah Jrame', 'ar' => 'سارة جريم'],
                'title' => ['en' => 'Lead Yoga Instructor', 'ar' => 'مدربة يوغا رئيسية'],
                'specialty' => ['en' => 'Vinyasa, Power Yoga, Restorative', 'ar' => 'فينياسا، يوغا القوة، الترميمية'],
                'bio' => ['en' => 'With over 10 years of experience, Sarah brings a mindful approach to every session.', 'ar' => 'مع أكثر من 10 سنوات من الخبرة، تقدم سارة نهجاً واعياً لكل جلسة.'],
                'social_links' => [['platform' => 'instagram', 'url' => '#'], ['platform' => 'twitter', 'url' => '#']],
                'image' => 'https://images.unsplash.com/photo-1594381898411-846e7d193883?auto=format&fit=crop&w=400&q=80',
            ],
            [
                'name' => ['en' => 'Adam Kim', 'ar' => 'آدم كيم'],
                'title' => ['en' => 'Pilates Specialist', 'ar' => 'أخصائي بيلاتس'],
                'specialty' => ['en' => 'Reformer, Mat Pilates, Core Strength', 'ar' => 'ريفورمر، بيلاتس الأرضية، تقوية المركز'],
                'bio' => ['en' => 'Former physical therapist turned Pilates master focusing on alignment and control.', 'ar' => 'معالج فيزيائي سابق تحول إلى خبير بيلاتس يركز على المحاذاة والتحكم.'],
                'social_links' => [['platform' => 'instagram', 'url' => '#'], ['platform' => 'linkedin', 'url' => '#']],
                'image' => 'https://images.unsplash.com/photo-1571019614242-c5c5dee9f50b?auto=format&fit=crop&w=400&q=80',
            ],
            [
                'name' => ['en' => 'Emma Wall', 'ar' => 'إيما وول'],
                'title' => ['en' => 'Pilates & Barre Instructor', 'ar' => 'مدربة بيلاتس وبار'],
                'specialty' => ['en' => 'Mat Pilates, Barre, Postnatal Fitness', 'ar' => 'بيلاتس الأرضية، بار، لياقة ما بعد الولادة'],
                'bio' => ['en' => 'Emma creates welcoming spaces for all fitness levels with a gentle but effective approach.', 'ar' => 'تخلق إيما مساحات ترحيبية لجميع مستويات اللياقة بأسلوب لطيف لكن فعال.'],
                'social_links' => [['platform' => 'instagram', 'url' => '#'], ['platform' => 'twitter', 'url' => '#']],
                'image' => 'https://images.unsplash.com/photo-1518611012118-696072aa579a?auto=format&fit=crop&w=400&q=80',
            ],
        ];

        foreach ($instructors as $data) {
            Instructor::firstOrCreate(
                ['name->en' => $data['name']['en']],
                $data
            );
        }
    }
}
