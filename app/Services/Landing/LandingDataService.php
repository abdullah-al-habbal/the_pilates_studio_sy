<?php

declare(strict_types=1);

namespace App\Services\Landing;

use App\Services\AppSetting\AppSettingService;
use App\Services\Classes\ClassesService;
use App\Services\ClassSession\ClassSessionService;
use App\Services\Instructor\InstructorService;
use App\Services\Package\PackageService;
use App\Services\Testimonial\TestimonialService;
use App\Services\StaticPage\StaticPageService;
use App\ValueObjects\Landing\LandingSettingsVO;
use App\ValueObjects\Landing\LandingClassVO;
use App\ValueObjects\Landing\LandingScheduleDayVO;
use App\ValueObjects\Landing\LandingSessionVO;
use App\ValueObjects\Landing\LandingInstructorVO;
use App\ValueObjects\Landing\LandingPackageVO;
use App\ValueObjects\Landing\LandingTestimonialVO;
use App\ValueObjects\Landing\LandingDataVO;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class LandingDataService
{
    public function __construct(
        private readonly AppSettingService $appSettingService,
        private readonly ClassesService $classesService,
        private readonly ClassSessionService $classSessionService,
        private readonly InstructorService $instructorService,
        private readonly PackageService $packageService,
        private readonly TestimonialService $testimonialService,
        private readonly StaticPageService $staticPageService
    ) {}

    public function getLandingData(): LandingDataVO
    {
        $hasError = false;
        $isEmpty = true;

        $settings = $this->getSettings();
        if ($settings === null) $hasError = true;

        $classes = $this->getClasses();
        if ($classes === null) $hasError = true;
        elseif ($classes->isNotEmpty()) $isEmpty = false;

        $schedule = $this->getSchedule();
        if ($schedule === null) $hasError = true;
        elseif ($schedule->isNotEmpty()) $isEmpty = false;

        $instructors = $this->getInstructors();
        if ($instructors === null) $hasError = true;
        elseif ($instructors->isNotEmpty()) $isEmpty = false;

        $packages = $this->getPackages();
        if ($packages === null) $hasError = true;
        elseif ($packages->isNotEmpty()) $isEmpty = false;

        $testimonials = $this->getTestimonials();
        if ($testimonials === null) $hasError = true;
        elseif ($testimonials->isNotEmpty()) $isEmpty = false;

        $staticPages = $this->getStaticPages();
        if ($staticPages === null) $hasError = true;

        return new LandingDataVO(
            settings: $settings ?? LandingSettingsVO::empty(),
            classes: $classes ?? collect(),
            schedule: $schedule ?? collect(),
            instructors: $instructors ?? collect(),
            packages: $packages ?? collect(),
            testimonials: $testimonials ?? collect(),
            staticPages: $staticPages ?? collect(),
            hasError: $hasError,
            isEmpty: $isEmpty
        );
    }

    private function getSettings(): ?LandingSettingsVO
    {
        try {
            $locale = app()->getLocale();
            return LandingSettingsVO::fromAppSettings($this->appSettingService, $locale);
        } catch (\Throwable $e) {
            report($e);
            return null;
        }
    }

    private function getClasses(): ?Collection
    {
        try {
            return $this->classesService->getActiveClassesForLanding()
                ->take(9)
                ->map(fn($c) => LandingClassVO::fromModel($c));
        } catch (\Throwable $e) {
            report($e);
            return null;
        }
    }

    private function getSchedule(): ?Collection
    {
        try {
            $start = Carbon::today();
            $end = Carbon::today()->addDays(6);
            $sessions = $this->classSessionService->getSessionsForWeek($start->toDateString(), $end->toDateString());
            $grouped = $sessions->groupBy('date');
            $days = collect();
            for ($i = 0; $i < 7; $i++) {
                $date = $start->copy()->addDays($i)->toDateString();
                $daySessions = $grouped->get($date, collect());
                $dayName = Carbon::parse($date)->translatedFormat('D');
                $days->push(new LandingScheduleDayVO(
                    date: $date,
                    dayName: $dayName,
                    sessions: $daySessions->map(fn($s) => LandingSessionVO::fromModel($s))
                ));
            }
            return $days;
        } catch (\Throwable $e) {
            report($e);
            return null;
        }
    }

    private function getInstructors(): ?Collection
    {
        try {
            return $this->instructorService->getActiveInstructorsWithProfile()
                ->take(5)
                ->map(fn($i) => LandingInstructorVO::fromModel($i));
        } catch (\Throwable $e) {
            report($e);
            return null;
        }
    }

    private function getPackages(): ?Collection
    {
        try {
            $packages = $this->packageService->getTopActivePackages(3);
            return $packages->map(fn($p) => LandingPackageVO::fromModel($p));
        } catch (\Throwable $e) {
            report($e);
            return null;
        }
    }

    private function getTestimonials(): ?Collection
    {
        try {
            $testimonials = $this->testimonialService->getActiveTestimonials();
            return $testimonials->map(fn($t) => LandingTestimonialVO::fromModel($t));
        } catch (\Throwable $e) {
            report($e);
            return null;
        }
    }

    private function getStaticPages(): ?Collection
    {
        try {
            return $this->staticPageService->getAllForFooter();
        } catch (\Throwable $e) {
            report($e);
            return null;
        }
    }
}
