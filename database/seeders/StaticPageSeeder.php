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
                'title'   => ['en' => 'About Us',       'ar' => 'من نحن'],
                'image'   => null,
                'content' => [
                    'en' => '<p>We are a premium Pilates studio dedicated to movement, strength, and mindful living.</p>',
                    'ar' => '<p>نحن استوديو بيلاتيس راقٍ مخصص للحركة والقوة والعيش الواعي.</p>',
                ],
            ],
            [
                'slug'    => 'privacy-policy',
                'title'   => ['en' => 'Privacy Policy',  'ar' => 'سياسة الخصوصية'],
                'image'   => null,
                'content' => [
                    'en' => '<p>Your privacy is important to us. This policy explains how we collect and protect your data.</p>',
                    'ar' => '<p>خصوصيتك مهمة بالنسبة لنا. تشرح هذه السياسة كيفية جمع بياناتك وحمايتها.</p>',
                ],
            ],
            [
                'slug'    => 'contact-us',
                'title'   => ['en' => 'Contact Us',      'ar' => 'اتصل بنا'],
                'image'   => null,
                'content' => [
                    'en' => '<p>Reach us at <strong>hello@studio.com</strong> or call <strong>+971 94 508 5594</strong>.</p>',
                    'ar' => '<p>تواصل معنا عبر <strong>hello@studio.com</strong> أو اتصل على <strong>+971 94 508 5594</strong>.</p>',
                ],
            ],
        ];

        foreach ($pages as $page) {
            StaticPage::firstOrCreate(['slug' => $page['slug']], $page);
        }
    }
}
