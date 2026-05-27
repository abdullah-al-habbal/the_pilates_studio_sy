<?php

// filePath: app/Providers/InitialDataServiceProvider.php

namespace App\Providers;

use App\Models\AppSetting;
use App\Models\StaticPage;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class InitialDataServiceProvider extends ServiceProvider
{
    private const REQUIRED_SETTINGS = [
        ['key' => 'maintenance_mode', 'value' => 'false', 'type' => 'boolean', 'description' => 'Whether the app is in maintenance mode'],
        ['key' => 'site_name', 'value' => '{"en":"The Pilates Studio App","ar":"تطبيق بيلاتس ستوديو"}', 'type' => 'json', 'description' => 'Site name'],
        ['key' => 'site_tagline', 'value' => '{"en":"Premium Yoga, Pilates & Dance Fitness","ar":"يوغا، بيلاتس ولياقة رقص"}', 'type' => 'json', 'description' => 'Site tagline'],
        ['key' => 'site_description', 'value' => '{"en":"Book classes, manage reservations, and purchase flexible credit packages all from your phone.","ar":"احجز الحصص، أدر الحجوزات، واشترِ باقات ائتمانية مرنة كل ذلك من هاتفك."}', 'type' => 'json', 'description' => 'Site description'],
        ['key' => 'site_logo', 'value' => '', 'type' => 'image', 'description' => 'Site logo URL'],
        ['key' => 'contact_email', 'value' => 'hello@thepilatesstudiocy.com', 'type' => 'string', 'description' => 'Contact email'],
        ['key' => 'contact_phone', 'value' => '+963 xxx xxx xxx', 'type' => 'string', 'description' => 'Contact phone'],
        ['key' => 'contact_address', 'value' => '{"en":"Damascus, Syria","ar":"دمشق، سوريا"}', 'type' => 'json', 'description' => 'Contact address'],
        ['key' => 'opening_hours_weekdays', 'value' => 'Mon–Fri: 6AM–9PM', 'type' => 'string', 'description' => 'Weekday opening hours'],
        ['key' => 'opening_hours_weekends', 'value' => 'Sat–Sun: 7AM–8PM', 'type' => 'string', 'description' => 'Weekend opening hours'],
        ['key' => 'hero_title', 'value' => '{"en":"Your Journey Starts Here","ar":"رحلتك تبدأ هنا"}', 'type' => 'json', 'description' => 'Hero title'],
        ['key' => 'hero_subtitle', 'value' => '{"en":"Book classes, manage reservations, and purchase flexible credit packages — all from your phone. Premium yoga, pilates, and dance fitness with expert instructors.","ar":"احجز الحصص، أدر الحجوزات، واشترِ باقات ائتمانية مرنة — كل ذلك من هاتفك. يوغا، بيلاتس، ولياقة رقص مع مدربين خبراء."}', 'type' => 'json', 'description' => 'Hero subtitle'],
        ['key' => 'hero_stats_classes', 'value' => '50', 'type' => 'number', 'description' => 'Number of weekly classes shown in hero'],
        ['key' => 'hero_stats_instructors', 'value' => '12', 'type' => 'number', 'description' => 'Number of instructors shown in hero'],
        ['key' => 'features_title', 'value' => '{"en":"Everything You Need, In Your Pocket","ar":"كل ما تحتاجه في جيبك"}', 'type' => 'json', 'description' => 'Features section title'],
        ['key' => 'features_subtitle', 'value' => '{"en":"Our mobile app puts the entire studio experience at your fingertips. Browse, book, and manage your fitness journey with ease.","ar":"تطبيقنا المحمول يضع تجربة الاستوديو بأكملها في متناول يدك. تصفح، احجز، وأدر رحلتك الرياضية بسهولة."}', 'type' => 'json', 'description' => 'Features section subtitle'],
        ['key' => 'classes_title', 'value' => '{"en":"Find Your Perfect Class","ar":"اعثر على حصتك المثالية"}', 'type' => 'json', 'description' => 'Classes section title'],
        ['key' => 'schedule_title', 'value' => '{"en":"This Week at The Pilates Studio Syria","ar":"هذا الأسبوع في بيلاتس ستوديو سوريا"}', 'type' => 'json', 'description' => 'Schedule section title'],
        ['key' => 'instructors_title', 'value' => '{"en":"Meet Our Instructors","ar":"تعرف على مدربينا"}', 'type' => 'json', 'description' => 'Instructors section title'],
        ['key' => 'packages_title', 'value' => '{"en":"Flexible Credit Packages","ar":"باقات ائتمانية مرنة"}', 'type' => 'json', 'description' => 'Packages section title'],
        ['key' => 'packages_subtitle', 'value' => '{"en":"Purchase credits and use them for any class. No commitments, no hidden fees.","ar":"اشترِ الاعتمادات واستخدمها لأي حصة. لا التزامات، ولا رسوم مخفية."}', 'type' => 'json', 'description' => 'Packages section subtitle'],
        ['key' => 'how_it_works_title', 'value' => '{"en":"Start in Three Simple Steps","ar":"ابدأ في ثلاث خطوات بسيطة"}', 'type' => 'json', 'description' => 'How it works section title'],
        ['key' => 'testimonials_title', 'value' => '{"en":"Loved by Our Community","ar":"محبوب من مجتمعنا"}', 'type' => 'json', 'description' => 'Testimonials section title'],
        ['key' => 'cta_title', 'value' => '{"en":"Ready to Start Your Journey?","ar":"هل أنت مستعد لبدء رحلتك؟"}', 'type' => 'json', 'description' => 'CTA section title'],
        ['key' => 'cta_subtitle', 'value' => '{"en":"Download the app today and get your first class free.","ar":"حمّل التطبيق اليوم واحصل على أول حصة مجاناً."}', 'type' => 'json', 'description' => 'CTA section subtitle'],
        ['key' => 'deep_link_scheme', 'value' => 'thepilatesstudio', 'type' => 'string', 'description' => 'Deep link scheme for the mobile app (without ://)'],
        ['key' => 'footer_copyright', 'value' => '{"en":"The Pilates Studio Syria","ar":"ذا بيلاتس ستوديو سوريا"}', 'type' => 'json', 'description' => 'Footer copyright text'],
        ['key' => 'social_instagram', 'value' => '{"url":"https://instagram.com/thepilatesstudio","icon":"instagram"}', 'type' => 'string', 'description' => 'Instagram link & icon (JSON with url and icon)'],
        ['key' => 'social_facebook', 'value' => '{"url":"https://facebook.com/thepilatesstudio","icon":"facebook"}', 'type' => 'string', 'description' => 'Facebook link & icon (JSON with url and icon)'],
        ['key' => 'social_twitter', 'value' => '{"url":"https://twitter.com/thepilatesst","icon":"twitter"}', 'type' => 'string', 'description' => 'Twitter link & icon (JSON with url and icon)'],
        ['key' => 'social_youtube', 'value' => '{"url":"https://youtube.com/@thepilatesstudio","icon":"youtube"}', 'type' => 'string', 'description' => 'Youtube link & icon (JSON with url and icon)'],
    ];

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

        if (!Schema::hasTable('app_settings') || !Schema::hasTable('static_pages')) {
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
        $existingKeys = AppSetting::query()->pluck('key')->all();

        foreach (self::REQUIRED_SETTINGS as $setting) {
            if (!in_array($setting['key'], $existingKeys, true)) {
                AppSetting::create($setting);
            }
        }
    }

    private function ensureStaticPages(): void
    {
        $existingSlugs = StaticPage::query()->pluck('slug')->all();

        foreach (self::REQUIRED_PAGES as $page) {
            if (!in_array($page['slug'], $existingSlugs, true)) {
                StaticPage::create($page);
            }
        }
    }
}
