<?php

namespace Database\Seeders;

use App\Models\StaticPage;
use Illuminate\Database\Seeder;

class StaticPageSeeder extends Seeder
{
    public function run(): void
    {
        $pages = [
            [
                'slug'    => 'about-us',
                'title'   => ['en' => 'About Us', 'ar' => 'من نحن'],
                'image'   => null,
                'content' => [
                    'en' => '<p>We are a premium Pilates studio dedicated to movement, strength, and mindful living.</p>',
                    'ar' => '<p>نحن استوديو بيلاتيس راقٍ مخصص للحركة والقوة والعيش الواعي.</p>',
                ],
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'slug'    => 'privacy-policy',
                'title'   => ['en' => 'Privacy Policy', 'ar' => 'سياسة الخصوصية'],
                'image'   => null,
                'content' => [
                    'en' => '<p>Your privacy is important to us. This policy explains how we collect and protect your data.</p>',
                    'ar' => '<p>خصوصيتك مهمة بالنسبة لنا. تشرح هذه السياسة كيفية جمع بياناتك وحمايتها.</p>',
                ],
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'slug'    => 'terms-of-service',
                'title'   => ['en' => 'Terms of Service', 'ar' => 'شروط الخدمة'],
                'image'   => null,
                'content' => [
                    'en' => '<p>Our terms of service outline the rules and guidelines for using our studio and app.</p>',
                    'ar' => '<p>توضح شروط الخدمة لدينا القواعد والإرشادات لاستخدام استوديو وتطبيقنا.</p>',
                ],
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'slug'    => 'cancellation-policy',
                'title'   => ['en' => 'Cancellation Policy', 'ar' => 'سياسة الإلغاء'],
                'image'   => null,
                'content' => [
                    'en' => '<p>You can cancel your booking up to 24 hours before the class starts and get your credits back.</p>',
                    'ar' => '<p>يمكنك إلغاء حجزك قبل 24 ساعة من بدء الحصة واستعادة اعتماداتك.</p>',
                ],
                'is_active' => true,
                'sort_order' => 4,
            ],
            [
                'slug'    => 'contact-us',
                'title'   => ['en' => 'Contact Us', 'ar' => 'اتصل بنا'],
                'image'   => null,
                'content' => [
                    'en' => '<p>Reach us at <strong>hello@thepilatesstudiocy.com</strong>.</p>',
                    'ar' => '<p>تواصل معنا عبر <strong>hello@thepilatesstudiocy.com</strong>.</p>',
                ],
                'is_active' => true,
                'sort_order' => 5,
            ],
        ];

        foreach ($pages as $page) {
            StaticPage::firstOrCreate(['slug' => $page['slug']], $page);
        }
    }
}
