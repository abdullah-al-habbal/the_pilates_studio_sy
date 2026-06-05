<?php

declare(strict_types=1);

namespace App\ValueObjects\Landing;

use App\Services\AppSetting\AppSettingService;

class LandingSettingsVO
{
    public function __construct(
        public readonly ?string $siteName,
        public readonly ?string $siteTagline,
        public readonly ?string $siteDescription,
        public readonly ?string $logoUrl,
        public readonly ?string $contactEmail,
        public readonly ?string $contactPhone,
        public readonly ?string $contactAddress,
        public readonly ?string $openingHoursWeekdays,
        public readonly ?string $openingHoursWeekends,
        public readonly ?string $heroTitle,
        public readonly ?string $heroSubtitle,
        public readonly ?int $heroStatsClasses,
        public readonly ?int $heroStatsInstructors,
        public readonly ?string $featuresTitle,
        public readonly ?string $featuresSubtitle,
        public readonly ?string $classesTitle,
        public readonly ?string $scheduleTitle,
        public readonly ?string $instructorsTitle,
        public readonly ?string $packagesTitle,
        public readonly ?string $packagesSubtitle,
        public readonly ?string $howItWorksTitle,
        public readonly ?string $testimonialsTitle,
        public readonly ?string $ctaTitle,
        public readonly ?string $ctaSubtitle,
        public readonly ?string $deepLinkScheme,
        public readonly ?string $footerCopyright,
        public readonly ?string $socialInstagram,
        public readonly ?string $socialFacebook,
        public readonly ?string $socialTwitter,
        public readonly ?string $socialYoutube,
        public readonly ?string $heroImage,
        public readonly string $brandPrimaryColor,
        public readonly string $brandSecondaryColor,
        public readonly string $brandAccentColor,
    ) {}

    public static function fromAppSettings(AppSettingService $service, string $locale): self
    {
        return new self(
            siteName: $service->getTranslated('site_name'),
            siteTagline: $service->getTranslated('site_tagline'),
            siteDescription: $service->getTranslated('site_description'),
            logoUrl: $service->get('site_logo'),
            contactEmail: $service->get('contact_email'),
            contactPhone: $service->get('contact_phone'),
            contactAddress: $service->getTranslated('contact_address'),
            openingHoursWeekdays: $service->get('opening_hours_weekdays'),
            openingHoursWeekends: $service->get('opening_hours_weekends'),
            heroTitle: $service->getTranslated('hero_title'),
            heroSubtitle: $service->getTranslated('hero_subtitle'),
            heroStatsClasses: $service->get('hero_stats_classes') !== null ? (int) $service->get('hero_stats_classes') : null,
            heroStatsInstructors: $service->get('hero_stats_instructors') !== null ? (int) $service->get('hero_stats_instructors') : null,
            featuresTitle: $service->getTranslated('features_title'),
            featuresSubtitle: $service->getTranslated('features_subtitle'),
            classesTitle: $service->getTranslated('classes_title'),
            scheduleTitle: $service->getTranslated('schedule_title'),
            instructorsTitle: $service->getTranslated('instructors_title'),
            packagesTitle: $service->getTranslated('packages_title'),
            packagesSubtitle: $service->getTranslated('packages_subtitle'),
            howItWorksTitle: $service->getTranslated('how_it_works_title'),
            testimonialsTitle: $service->getTranslated('testimonials_title'),
            ctaTitle: $service->getTranslated('cta_title'),
            ctaSubtitle: $service->getTranslated('cta_subtitle'),
            deepLinkScheme: $service->get('deep_link_scheme'),
            footerCopyright: $service->getTranslated('footer_copyright'),
            socialInstagram: $service->get('social_instagram'),
            socialFacebook: $service->get('social_facebook'),
            socialTwitter: $service->get('social_twitter'),
            socialYoutube: $service->get('social_youtube'),
            heroImage: $service->get('hero_image') ?: null,
            brandPrimaryColor: $service->get('brand_primary_color', '#262D35'),
            brandSecondaryColor: $service->get('brand_secondary_color', '#F3EFE3'),
            brandAccentColor: $service->get('brand_accent_color', '#B8A18B'),
        );
    }

    public static function empty(): self
    {
        return new self(
            siteName: null,
            siteTagline: null,
            siteDescription: null,
            logoUrl: null,
            contactEmail: null,
            contactPhone: null,
            contactAddress: null,
            openingHoursWeekdays: null,
            openingHoursWeekends: null,
            heroTitle: null,
            heroSubtitle: null,
            heroStatsClasses: null,
            heroStatsInstructors: null,
            featuresTitle: null,
            featuresSubtitle: null,
            classesTitle: null,
            scheduleTitle: null,
            instructorsTitle: null,
            packagesTitle: null,
            packagesSubtitle: null,
            howItWorksTitle: null,
            testimonialsTitle: null,
            ctaTitle: null,
            ctaSubtitle: null,
            deepLinkScheme: null,
            footerCopyright: null,
            socialInstagram: null,
            socialFacebook: null,
            socialTwitter: null,
            socialYoutube: null,
            heroImage: null,
            brandPrimaryColor: '#262D35',
            brandSecondaryColor: '#F3EFE3',
            brandAccentColor: '#B8A18B',
        );
    }
}
