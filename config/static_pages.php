<?php

return [
    'defaults' => [
        [
            'slug'       => 'about-us',
            'title'      => ['en' => 'About Us', 'ar' => 'من نحن'],
            'image'      => null,
            'content'    => [
                'en' => '<p>We are a premium Pilates studio dedicated to movement, strength, and mindful living.</p>',
                'ar' => '<p>نحن استوديو بيلاتيس راقٍ مخصص للحركة والقوة والعيش الواعي.</p>',
            ],
            'is_active'  => true,
            'sort_order' => 1,
        ],
        [
            'slug'       => 'privacy-policy',
            'title'      => ['en' => 'Privacy Policy', 'ar' => 'سياسة الخصوصية'],
            'image'      => null,
            'content'    => [
                'en' => '<h2>Privacy Policy</h2>
<p>We built the Pilates Studio app to make booking your classes as easy as possible. This policy explains what information we collect, why we collect it, and how we use it — in plain language.</p>

<h3>What We Collect</h3>
<p>When you use the app:</p>
<ul>
<li>Your booking and session history</li>
<li>Whether you\'ve turned push notifications on or off</li>
</ul>
<p>Automatically:</p>
<ul>
<li>A device identifier — a unique code that helps us keep your account secure</li>
<li>Crash reports and performance data to help us fix bugs and improve the app</li>
</ul>

<h3>Calendar Access</h3>
<p>If you choose to save a booked session to your calendar, we ask for calendar permission. We only use this to add the event to your device — nothing from your calendar is ever sent to our servers.</p>

<h3>Push Notifications</h3>
<p>We send notifications to remind you about upcoming classes and bookings. You can turn these off at any time from the app\'s Settings or your phone\'s notification settings.</p>

<h3>Third-Party Services</h3>
<p>We use a small number of trusted third-party services to run the app:</p>
<table>
<tr><th>Service</th><th>Purpose</th></tr>
<tr><td>Firebase (Google)</td><td>Push notifications</td></tr>
<tr><td>Datadog</td><td>Crash reporting & app performance</td></tr>
</table>
<p>These services may receive limited technical data (such as your device type or app version) to perform their function. They are not permitted to use this data for their own purposes.</p>

<h3>Your Data, Your Rights</h3>
<p>You\'re in control of your data:</p>
<ul>
<li><strong>Access</strong> — you can view your profile and booking history inside the app at any time</li>
<li><strong>Delete your account</strong> — go to Settings → Delete Account to permanently remove your account and all associated data</li>
<li><strong>Notification preferences</strong> — manage them anytime from Settings</li>
</ul>

<h3>How Long We Keep Your Data</h3>
<p>We keep your data for as long as your account is active. If you delete your account, your personal data is permanently removed from our systems.</p>

<h3>Contact Us</h3>
<p>If you have any questions about this privacy policy or your data, please reach out:</p>
<p>hello@thepilatesstudiocy.com</p>',
                'ar' => '<p>خصوصيتك مهمة بالنسبة لنا. تشرح هذه السياسة كيفية جمع بياناتك وحمايتها.</p>',
            ],
            'is_active'  => true,
            'sort_order' => 2,
        ],
        [
            'slug'       => 'terms-of-service',
            'title'      => ['en' => 'Terms of Service', 'ar' => 'شروط الخدمة'],
            'image'      => null,
            'content'    => [
                'en' => '<p>Our terms of service outline the rules and guidelines for using our studio and app.</p>',
                'ar' => '<p>توضح شروط الخدمة لدينا القواعد والإرشادات لاستخدام استوديو وتطبيقنا.</p>',
            ],
            'is_active'  => true,
            'sort_order' => 3,
        ],
        [
            'slug'       => 'cancellation-policy',
            'title'      => ['en' => 'Cancellation Policy', 'ar' => 'سياسة الإلغاء'],
            'image'      => null,
            'content'    => [
                'en' => '<p>You can cancel your booking up to 24 hours before the class starts and get your credits back.</p>',
                'ar' => '<p>يمكنك إلغاء حجزك قبل 24 ساعة من بدء الحصة واستعادة اعتماداتك.</p>',
            ],
            'is_active'  => true,
            'sort_order' => 4,
        ],
        [
            'slug'       => 'contact-us',
            'title'      => ['en' => 'Contact Us', 'ar' => 'اتصل بنا'],
            'image'      => null,
            'content'    => [
                'en' => '<p>Reach us at <strong>hello@thepilatesstudiocy.com</strong>.</p>',
                'ar' => '<p>تواصل معنا عبر <strong>hello@thepilatesstudiocy.com</strong>.</p>',
            ],
            'is_active'  => true,
            'sort_order' => 5,
        ],
    ],
];