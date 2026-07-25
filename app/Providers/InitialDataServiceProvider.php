<?php

namespace App\Providers;

use App\Models\AppSetting;
use App\Models\StaticPage;
use Database\Factories\Concerns\CopiesSourceImage;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class InitialDataServiceProvider extends ServiceProvider
{
    use CopiesSourceImage;

    private const REQUIRED_PAGES = [
        [
            'slug' => 'about-us',
            'title' => ['en' => 'About Us', 'ar' => 'من نحن'],
            'content' => ['en' => '<p>We are a premium Pilates studio dedicated to movement, strength, and mindful living.</p>', 'ar' => '<p>نحن استوديو بيلاتيس راقٍ مخصص للحركة والقوة والعيش الواعي.</p>'],
            'is_active' => true,
            'sort_order' => 1,
        ],
        [
            'slug' => 'privacy-policy',
            'title' => ['en' => 'Privacy Policy', 'ar' => 'سياسة الخصوصية'],
            'content' => ['en' => '<p>Your privacy is important to us. This policy explains how we collect and protect your data.</p>', 'ar' => '<p>خصوصيتك مهمة بالنسبة لنا. تشرح هذه السياسة كيفية جمع بياناتك وحمايتها.</p>'],
            'is_active' => true,
            'sort_order' => 2,
        ],
        [
            'slug' => 'terms-of-service',
            'title' => ['en' => 'Terms of Service', 'ar' => 'شروط الخدمة'],
            'content' => ['en' => '<p>Our terms of service outline the rules and guidelines for using our studio and app.</p>', 'ar' => '<p>توضح شروط الخدمة لدينا القواعد والإرشادات لاستخدام استوديو وتطبيقنا.</p>'],
            'is_active' => true,
            'sort_order' => 3,
        ],
        [
            'slug' => 'cancellation-policy',
            'title' => ['en' => 'Cancellation Policy', 'ar' => 'سياسة الإلغاء'],
            'content' => ['en' => '<p>You can cancel your booking up to 24 hours before the class starts and get your credits back.</p>', 'ar' => '<p>يمكنك إلغاء حجزك قبل 24 ساعة من بدء الحصة واستعادة اعتماداتك.</p>'],
            'is_active' => true,
            'sort_order' => 4,
        ],
        [
            'slug' => 'contact-us',
            'title' => ['en' => 'Contact Us', 'ar' => 'اتصل بنا'],
            'content' => ['en' => '<p>Reach us at <strong>hello@thepilatesstudiocy.com</strong>.</p>', 'ar' => '<p>تواصل معنا عبر <strong>hello@thepilatesstudiocy.com</strong>.</p>'],
            'is_active' => true,
            'sort_order' => 5,
        ],
    ];

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            return;
        }

        if (! Schema::hasTable('app_settings') || ! Schema::hasTable('static_pages')) {
            return;
        }

        try {
            $this->ensureAppSettings();
            $this->ensureStaticPages();
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private function ensureAppSettings(): void
    {
        $settings = Config::get('app_settings.defaults', []);
        $sourcePath = public_path('assets/images/website/landing_page/hero_section/hero_image.webp');
        $logoPath = public_path('assets/images/website/landing_page/hero_section/logo.jpg');

        foreach ($settings as $setting) {
            $existingSetting = AppSetting::where('key', $setting['key'])->first();

            if (! $existingSetting) {
                if (($setting['key'] ?? null) === 'site_logo') {
                    if (File::exists($logoPath)) {
                        $setting['value'] = $this->copySourceImage($logoPath, 'app-settings', 'site-logo', 'logo');
                    }
                } elseif (($setting['type'] ?? null) === 'image') {
                    $identifier = str_replace('_', '-', $setting['key']);
                    $setting['value'] = $this->copySourceImage($sourcePath, 'app-settings', $identifier, $setting['key']);
                }

                AppSetting::create($setting);

                continue;
            }

            if ($setting['key'] === 'site_logo' && empty($existingSetting->value)) {
                if (File::exists($logoPath)) {
                    $existingSetting->value = $this->copySourceImage($logoPath, 'app-settings', 'site-logo', 'logo');
                    $existingSetting->save();
                }
            } elseif (($setting['type'] ?? null) === 'image' && empty($existingSetting->value)) {
                $identifier = str_replace('_', '-', $setting['key']);
                $existingSetting->value = $this->copySourceImage($sourcePath, 'app-settings', $identifier, $setting['key']);
                $existingSetting->save();
            }
        }
    }

    private function ensureStaticPages(): void
    {
        $sourcePath = public_path('assets/images/website/landing_page/hero_section/hero_image.webp');

        foreach (self::REQUIRED_PAGES as $page) {
            $existingPage = StaticPage::where('slug', $page['slug'])->first();

            if (! $existingPage) {
                $page['image'] = $this->copySourceImage($sourcePath, 'static-pages', $page['slug'], 'page');
                StaticPage::create($page);

                continue;
            }

            if (empty($existingPage->image)) {
                $existingPage->image = $this->copySourceImage($sourcePath, 'static-pages', $page['slug'], 'page');
                $existingPage->save();
            }
        }
    }
}
