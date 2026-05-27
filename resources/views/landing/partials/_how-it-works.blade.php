@php $s = $landingData->settings; @endphp
<section id="how-it-works" class="py-24 bg-white dark:bg-dark-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto mb-16 reveal">
            <span class="inline-block px-4 py-1.5 bg-primary-50 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300 text-sm font-semibold rounded-full mb-4">{{ __('landing.how_it_works') }}</span>
            <h2 class="text-3xl sm:text-4xl font-bold text-slate-900 dark:text-white mb-4">{{ $s->howItWorksTitle }}</h2>
            <p class="text-lg text-slate-600 dark:text-slate-300">{{ __('landing.getting_started_is_easy') }}</p>
        </div>

        <div class="relative max-w-4xl mx-auto">
            <div class="hidden md:block absolute top-24 left-[16.67%] right-[16.67%] h-0.5 bg-gradient-to-r from-primary-300 via-primary-500 to-primary-300 dark:from-primary-800 dark:via-primary-600 dark:to-primary-800"></div>

            <div class="grid md:grid-cols-3 gap-12 md:gap-8">
                @php
                $steps = [
                    ['number' => '01', 'icon' => 'download', 'title' => __('landing.download_app'), 'description' => 'Get the app from the App Store or Google Play. Set up your profile in under 2 minutes.', 'color' => 'primary'],
                    ['number' => '02', 'icon' => 'credit-card', 'title' => __('landing.nav_pricing'), 'description' => 'Browse our flexible credit packages. Purchase credits securely in the app.', 'color' => 'accent'],
                    ['number' => '03', 'icon' => 'calendar-check', 'title' => __('landing.book_class'), 'description' => 'Browse the schedule, pick your class, and reserve your spot with one tap.', 'color' => 'teal'],
                ];
                @endphp

                @foreach($steps as $index => $step)
                <div class="reveal relative text-center" style="transition-delay: {{ $index * 0.15 }}s;">
                    <div class="relative z-10 w-20 h-20 mx-auto mb-6 rounded-2xl bg-{{ $step['color'] }}-50 dark:bg-{{ $step['color'] }}-900/20 border-2 border-{{ $step['color'] }}-200 dark:border-{{ $step['color'] }}-800 flex items-center justify-center">
                        <div class="absolute -top-3 -right-3 w-8 h-8 rounded-full bg-{{ $step['color'] }}-500 text-white text-sm font-bold flex items-center justify-center shadow-lg">
                            {{ $step['number'] }}
                        </div>
                        <i data-lucide="{{ $step['icon'] }}" class="w-8 h-8 text-{{ $step['color'] }}-600 dark:text-{{ $step['color'] }}-400"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-3">{{ $step['title'] }}</h3>
                    <p class="text-slate-600 dark:text-slate-400 leading-relaxed">{{ $step['description'] }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</section>
