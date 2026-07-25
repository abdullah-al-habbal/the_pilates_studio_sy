<?php

declare(strict_types=1);

namespace App\ValueObjects\Landing;

use App\Services\AppSetting\AppSettingService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
        public readonly ?string $metaTitle,
        public readonly ?string $metaDescription,
        public readonly ?string $ogImageUrl,
        public readonly ?string $faviconUrl,
        public readonly string $structuredDataJson,
        public readonly array $socialUrls,
    ) {}

    public static function fromAppSettings(AppSettingService $service): self
    {
        $siteName = $service->getTranslated('site_name');
        $siteTagline = $service->getTranslated('site_tagline');
        $siteDescription = $service->getTranslated('site_description');
        $logoUrl = $service->get('site_logo') ? Storage::disk('public')->url($service->get('site_logo')) : null;
        $heroImage = $service->get('hero_image') ? Storage::disk('public')->url($service->get('hero_image')) : null;

        return new self(
            siteName: $siteName,
            siteTagline: $siteTagline,
            siteDescription: $siteDescription,
            logoUrl: $logoUrl,
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
            heroImage: $heroImage,
            brandPrimaryColor: $service->get('brand_primary_color', '#262D35'),
            brandSecondaryColor: $service->get('brand_secondary_color', '#F3EFE3'),
            brandAccentColor: $service->get('brand_accent_color', '#B8A18B'),
            metaTitle: self::buildMetaTitle($siteName, $siteTagline),
            metaDescription: self::buildMetaDescription($siteDescription),
            ogImageUrl: $logoUrl ?? $heroImage,
            faviconUrl: $logoUrl,
            structuredDataJson: self::buildStructuredData(
                siteName: $siteName,
                description: $siteDescription,
                ogImageUrl: $logoUrl ?? $heroImage,
                phone: $service->get('contact_phone'),
                address: $service->getTranslated('contact_address'),
                weekdays: $service->get('opening_hours_weekdays'),
                weekends: $service->get('opening_hours_weekends'),
                socialInstagram: $service->get('social_instagram'),
                socialFacebook: $service->get('social_facebook'),
                socialTwitter: $service->get('social_twitter'),
                socialYoutube: $service->get('social_youtube'),
            ),
            socialUrls: self::extractSocialUrls(
                instagram: $service->get('social_instagram'),
                facebook: $service->get('social_facebook'),
                twitter: $service->get('social_twitter'),
                youtube: $service->get('social_youtube'),
            ),
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
            metaTitle: null,
            metaDescription: null,
            ogImageUrl: null,
            faviconUrl: null,
            structuredDataJson: '{}',
            socialUrls: [],
        );
    }

    private static function buildMetaTitle(?string $name, ?string $tagline): ?string
    {
        if (! $name) {
            return null;
        }

        return $tagline ? "{$name} — {$tagline}" : $name;
    }

    private static function buildMetaDescription(?string $description): ?string
    {
        if (! $description) {
            return null;
        }

        return Str::limit($description, 155);
    }

    private static function buildStructuredData(
        ?string $siteName,
        ?string $description,
        ?string $ogImageUrl,
        ?string $phone,
        ?string $address,
        ?string $weekdays,
        ?string $weekends,
        ?string $socialInstagram,
        ?string $socialFacebook,
        ?string $socialTwitter,
        ?string $socialYoutube,
    ): string {
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'LocalBusiness',
            'name' => $siteName ?? '',
            'image' => $ogImageUrl ?? '',
            'description' => $description ?? '',
            'url' => url('/'),
            'telephone' => $phone ?? '',
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => $address ?? '',
                'addressLocality' => 'Damascus',
                'addressCountry' => 'SY',
            ],
            'geo' => [
                '@type' => 'GeoCoordinates',
                'latitude' => '33.519551395479155',
                'longitude' => '36.27795941553415',
            ],
            'priceRange' => '$$',
            'sameAs' => array_values(array_filter([
                self::extractUrl($socialInstagram),
                self::extractUrl($socialFacebook),
                self::extractUrl($socialTwitter),
                self::extractUrl($socialYoutube),
            ])),
        ];

        if ($weekdays || $weekends) {
            $data['openingHoursSpecification'] = [];
            if ($weekdays) {
                $data['openingHoursSpecification'][] = [
                    '@type' => 'OpeningHoursSpecification',
                    'dayOfWeek' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
                    'description' => $weekdays,
                ];
            }
            if ($weekends) {
                $data['openingHoursSpecification'][] = [
                    '@type' => 'OpeningHoursSpecification',
                    'dayOfWeek' => ['Saturday', 'Sunday'],
                    'description' => $weekends,
                ];
            }
        }

        return json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }

    private static function extractSocialUrls(
        ?string $instagram,
        ?string $facebook,
        ?string $twitter,
        ?string $youtube,
    ): array {
        return array_values(array_filter([
            self::extractUrl($instagram),
            self::extractUrl($facebook),
            self::extractUrl($twitter),
            self::extractUrl($youtube),
        ]));
    }

    private static function extractUrl(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        $decoded = json_decode($value, true);
        if (json_last_error() === JSON_ERROR_NONE && isset($decoded['url'])) {
            return $decoded['url'];
        }

        return null;
    }
}
