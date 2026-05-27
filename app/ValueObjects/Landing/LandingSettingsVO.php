<?php

declare(strict_types=1);

namespace App\ValueObjects\Landing;

use App\Services\AppSetting\AppSettingService;

class LandingSettingsVO
{
    public function __construct(
        public readonly string $siteName,
        public readonly string $siteTagline,
        public readonly string $siteDescription,
        public readonly string $logoUrl,
        public readonly string $contactEmail,
        public readonly string $contactPhone,
        public readonly string $contactAddress,
        public readonly string $openingHoursWeekdays,
        public readonly string $openingHoursWeekends,
        public readonly string $heroTitle,
        public readonly string $heroSubtitle,
        public readonly int $heroStatsClasses,
        public readonly int $heroStatsInstructors,
        public readonly string $featuresTitle,
        public readonly string $featuresSubtitle,
        public readonly string $classesTitle,
        public readonly string $scheduleTitle,
        public readonly string $instructorsTitle,
        public readonly string $packagesTitle,
        public readonly string $packagesSubtitle,
        public readonly string $howItWorksTitle,
        public readonly string $testimonialsTitle,
        public readonly string $ctaTitle,
        public readonly string $ctaSubtitle,
        public readonly string $footerCopyright,
        public readonly string $socialInstagram,
        public readonly string $socialFacebook,
        public readonly string $socialTwitter,
        public readonly string $socialYoutube,
    ) {}

    public static function fromAppSettings(AppSettingService $service, string $locale): self
    {
        return new self(
            siteName:              $service->getTranslated('site_name') ?? 'The Pilates Studio App',
            siteTagline:           $service->getTranslated('site_tagline') ?? 'Premium Yoga, Pilates & Dance Fitness',
            siteDescription:       $service->getTranslated('site_description') ?? '',
            logoUrl:               $service->get('site_logo') ?: asset('images/default-logo.png'),
            contactEmail:          $service->get('contact_email') ?? 'hello@thepilatesstudiocy.com',
            contactPhone:          $service->get('contact_phone') ?? '',
            contactAddress:        $service->getTranslated('contact_address') ?? 'Damascus, Syria',
            openingHoursWeekdays:  $service->get('opening_hours_weekdays') ?? '06:00-21:00',
            openingHoursWeekends:  $service->get('opening_hours_weekends') ?? '07:00-20:00',
            heroTitle:             $service->getTranslated('hero_title') ?? 'Your Journey Starts Here',
            heroSubtitle:          $service->getTranslated('hero_subtitle') ?? '',
            heroStatsClasses:      (int) ($service->get('hero_stats_classes') ?? 50),
            heroStatsInstructors:  (int) ($service->get('hero_stats_instructors') ?? 12),
            featuresTitle:         $service->getTranslated('features_title') ?? 'Everything You Need, In Your Pocket',
            featuresSubtitle:      $service->getTranslated('features_subtitle') ?? '',
            classesTitle:          $service->getTranslated('classes_title') ?? 'Find Your Perfect Class',
            scheduleTitle:         $service->getTranslated('schedule_title') ?? 'This Week at The Pilates Studio Syria',
            instructorsTitle:      $service->getTranslated('instructors_title') ?? 'Meet Our Instructors',
            packagesTitle:         $service->getTranslated('packages_title') ?? 'Flexible Credit Packages',
            packagesSubtitle:      $service->getTranslated('packages_subtitle') ?? '',
            howItWorksTitle:       $service->getTranslated('how_it_works_title') ?? 'Start in Three Simple Steps',
            testimonialsTitle:     $service->getTranslated('testimonials_title') ?? 'Loved by Our Community',
            ctaTitle:              $service->getTranslated('cta_title') ?? 'Ready to Start Your Journey?',
            ctaSubtitle:           $service->getTranslated('cta_subtitle') ?? '',
            footerCopyright:       $service->get('footer_copyright') ?? 'The Pilates Studio Syria',
            socialInstagram:       $service->get('social_instagram') ?? '#',
            socialFacebook:        $service->get('social_facebook') ?? '#',
            socialTwitter:         $service->get('social_twitter') ?? '#',
            socialYoutube:         $service->get('social_youtube') ?? '#',
        );
    }

    public static function default(): self
    {
        return new self(
            siteName: 'The Pilates Studio App',
            siteTagline: 'Premium Yoga, Pilates & Dance Fitness',
            siteDescription: '',
            logoUrl: asset('images/default-logo.png'),
            contactEmail: 'hello@thepilatesstudiocy.com',
            contactPhone: '',
            contactAddress: 'Damascus, Syria',
            openingHoursWeekdays: '06:00-21:00',
            openingHoursWeekends: '07:00-20:00',
            heroTitle: 'Your Journey Starts Here',
            heroSubtitle: '',
            heroStatsClasses: 50,
            heroStatsInstructors: 12,
            featuresTitle: 'Everything You Need, In Your Pocket',
            featuresSubtitle: '',
            classesTitle: 'Find Your Perfect Class',
            scheduleTitle: 'This Week',
            instructorsTitle: 'Meet Our Instructors',
            packagesTitle: 'Flexible Credit Packages',
            packagesSubtitle: '',
            howItWorksTitle: 'Start in Three Simple Steps',
            testimonialsTitle: 'Loved by Our Community',
            ctaTitle: 'Ready to Start Your Journey?',
            ctaSubtitle: '',
            footerCopyright: 'The Pilates Studio Syria',
            socialInstagram: '#',
            socialFacebook: '#',
            socialTwitter: '#',
            socialYoutube: '#',
        );
    }
}
