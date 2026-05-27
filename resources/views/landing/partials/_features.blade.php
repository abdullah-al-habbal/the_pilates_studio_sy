@php $s = $landingData->settings; @endphp
<section id="features" class="py-24 bg-slate-50 dark:bg-dark-800/50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto mb-16 reveal">
            <span class="inline-block px-4 py-1.5 bg-primary-50 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300 text-sm font-semibold rounded-full mb-4">{{ __('landing.app_features') }}</span>
            <h2 class="text-3xl sm:text-4xl font-bold text-slate-900 dark:text-white mb-4">{{ $s->featuresTitle }}</h2>
            <p class="text-lg text-slate-600 dark:text-slate-300">{{ $s->featuresSubtitle }}</p>
        </div>

        @php
        $features = [
            ['icon' => 'calendar-check', 'title' => __('landing.nav_classes'), 'description' => $s->featuresSubtitle ?: 'Browse and reserve class sessions in seconds. Real-time availability with one-tap reservation.', 'color' => 'primary'],
            ['icon' => 'credit-card', 'title' => __('landing.nav_pricing'), 'description' => 'Flexible packages that fit your schedule. Buy credits and use them whenever you want.', 'color' => 'accent'],
            ['icon' => 'bell', 'title' => 'Smart Notifications', 'description' => 'Never miss a class with push reminders. Get alerts for upcoming sessions and schedule changes.', 'color' => 'teal'],
            ['icon' => 'smartphone', 'title' => 'Manage On The Go', 'description' => 'Cancel or reschedule from the app. Credits are handled automatically with our easy cancellation policy.', 'color' => 'primary'],
        ];
        @endphp

        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
            @foreach($features as $index => $feature)
            <div class="reveal group" style="transition-delay: {{ $index * 0.1 }}s;">
                <div class="relative bg-white dark:bg-dark-800 rounded-2xl p-8 h-full border border-slate-100 dark:border-dark-700 hover-lift hover:border-primary-200 dark:hover:border-primary-800 transition-colors">
                    <div class="w-14 h-14 rounded-2xl bg-{{ $feature['color'] }}-50 dark:bg-{{ $feature['color'] }}-900/20 flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                        <i data-lucide="{{ $feature['icon'] }}" class="w-7 h-7 text-{{ $feature['color'] }}-600 dark:text-{{ $feature['color'] }}-400"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-3">{{ $feature['title'] }}</h3>
                    <p class="text-slate-600 dark:text-slate-400 leading-relaxed">{{ $feature['description'] }}</p>
                </div>
            </div>
            @endforeach
        </div>

        <div class="mt-16 reveal">
            <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-primary-600 to-teal-600 p-8 sm:p-12">
                <div class="absolute top-0 right-0 w-96 h-96 bg-white/10 rounded-full -translate-y-1/2 translate-x-1/4 blur-3xl"></div>
                <div class="absolute bottom-0 left-0 w-72 h-72 bg-white/10 rounded-full translate-y-1/2 -translate-x-1/4 blur-3xl"></div>

                <div class="relative flex flex-col lg:flex-row items-center justify-between gap-8">
                    <div class="text-center lg:text-left">
                        <h3 class="text-2xl sm:text-3xl font-bold text-white mb-3">{{ __('landing.download_the_app', ['app' => $s->siteName]) }}</h3>
                        <p class="text-white/80 text-lg max-w-xl">{{ __('landing.app_description') }}</p>
                    </div>
                    <div class="flex flex-col sm:flex-row gap-4">
                        <button class="inline-flex items-center gap-3 px-6 py-3 bg-white text-slate-900 rounded-xl font-semibold hover:bg-slate-50 transition-colors shadow-lg">
                            <svg class="w-7 h-7" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.8-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M13 3.5c.73-.83 1.21-1.98 1.08-3.11-1.05.05-2.31.7-3.06 1.55-.67.76-1.26 1.97-1.1 3.12 1.17.09 2.36-.66 3.08-1.56z"/>
                            </svg>
                            <div class="text-left">
                                <div class="text-xs text-slate-500">{{ __('landing.download_on_app_store') }}</div>
                            </div>
                        </button>
                        <button class="inline-flex items-center gap-3 px-6 py-3 bg-white text-slate-900 rounded-xl font-semibold hover:bg-slate-50 transition-colors shadow-lg">
                            <svg class="w-7 h-7" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M3 20.5v-17c0-.83.67-1.5 1.5-1.5.33 0 .65.1.92.29l14.5 8.5c.55.32.74 1.03.42 1.58-.1.18-.24.32-.42.42l-14.5 8.5c-.55.32-1.26.13-1.58-.42-.1-.18-.16-.38-.16-.58l.28-.29z"/>
                            </svg>
                            <div class="text-left">
                                <div class="text-xs text-slate-500">{{ __('landing.get_it_on_google_play') }}</div>
                            </div>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
