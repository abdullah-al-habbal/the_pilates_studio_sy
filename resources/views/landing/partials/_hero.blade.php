{{-- /home/lenovo/work/projects/pilates/resources/views/landing/partials/_hero.blade.php --}}
@php $s = $landingData->settings; @endphp
<section id="hero" class="relative min-h-screen flex items-center overflow-hidden pt-20">
    @php $heroImageUrl = $s->heroImage ? asset($s->heroImage) : null; @endphp

    @if($heroImageUrl)
    <div class="absolute inset-0 z-0">
        <img src="{{ $heroImageUrl }}"
             alt="Hero background"
             class="w-full h-full object-cover opacity-90 dark:opacity-40"
             loading="eager">
    </div>
    @endif
    <div class="absolute inset-0 bg-gradient-to-r from-white/95 via-white/80 to-white/40 dark:from-dark-900/95 dark:via-dark-900/80 dark:to-dark-900/40"></div>
    <div class="absolute inset-0 bg-gradient-to-t from-white dark:from-dark-900 via-transparent to-transparent"></div>

    <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 lg:py-32">
        <div class="grid lg:grid-cols-2 gap-12 items-center">
            <div class="reveal">
                <div class="inline-flex items-center gap-2 px-4 py-2 bg-primary-50 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300 text-sm font-medium rounded-full mb-6 border border-primary-100 dark:border-primary-800">
                    <span class="w-2 h-2 bg-primary-500 rounded-full animate-pulse"></span>
                    {{ $s->siteTagline }}
                </div>

                <h1 class="text-5xl sm:text-6xl lg:text-7xl font-extrabold tracking-tight text-slate-900 dark:text-white leading-[1.1] mb-6">
                    {!! nl2br(e($s->heroTitle)) !!}
                </h1>

                <p class="text-lg sm:text-xl text-slate-600 dark:text-slate-300 leading-relaxed mb-8 max-w-lg">
                    {{ $s->heroSubtitle }}
                </p>

                <div class="flex flex-col sm:flex-row gap-4 mb-12">
                    <a href="#download" class="inline-flex items-center justify-center gap-2 px-8 py-4 bg-primary-600 hover:bg-primary-700 text-white font-semibold rounded-xl transition-all hover:shadow-xl hover:shadow-primary-500/25 hover:-translate-y-0.5">
                        <i data-lucide="calendar-check" class="w-5 h-5"></i>
                        {{ __('landing.view_schedule') }}
                    </a>
                    <a href="#download" class="inline-flex items-center justify-center gap-2 px-8 py-4 bg-white dark:bg-dark-800 text-slate-700 dark:text-slate-200 font-semibold rounded-xl border border-slate-200 dark:border-dark-700 hover:border-primary-300 dark:hover:border-primary-700 hover:bg-primary-50 dark:hover:bg-primary-900/20 transition-all hover:-translate-y-0.5">
                        <i data-lucide="smartphone" class="w-5 h-5"></i>
                        {{ __('landing.get_app') }}
                    </a>
                </div>

                <div class="flex flex-wrap gap-6 sm:gap-10">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-xl bg-primary-50 dark:bg-primary-900/30 flex items-center justify-center">
                            <i data-lucide="dumbbell" class="w-6 h-6 text-primary-600 dark:text-primary-400"></i>
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-slate-900 dark:text-white">{{ $s->heroStatsClasses }}+</div>
                            <div class="text-sm text-slate-500 dark:text-slate-400">{{ __('landing.nav_classes') }}</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-xl bg-accent-50 dark:bg-accent-900/20 flex items-center justify-center">
                            <i data-lucide="users" class="w-6 h-6 text-accent-500"></i>
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-slate-900 dark:text-white">{{ $s->heroStatsInstructors }}</div>
                            <div class="text-sm text-slate-500 dark:text-slate-400">{{ __('landing.nav_instructors') }}</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-xl bg-accent-50 dark:bg-accent-900/20 flex items-center justify-center">
                            <i data-lucide="zap" class="w-6 h-6 text-accent-600 dark:text-accent-400"></i>
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-slate-900 dark:text-white">{{ __('landing.nav_pricing') }}</div>
                            <div class="text-sm text-slate-500 dark:text-slate-400">{{ __('landing.nav_download_app') }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="hidden lg:block reveal" style="transition-delay: 0.2s;">
                <div class="relative">
                    <div class="absolute -top-10 -right-10 w-72 h-72 bg-primary-200/50 dark:bg-primary-900/20 rounded-full blur-3xl"></div>
                    <div class="absolute -bottom-10 -left-10 w-72 h-72 bg-accent-200/50 dark:bg-accent-900/20 rounded-full blur-3xl"></div>

                    <div class="relative mx-auto w-[320px] h-[640px] bg-dark-800 phone-mockup overflow-hidden border-4 border-slate-800 dark:border-slate-700">
                        <div class="absolute inset-0 bg-gradient-to-b from-primary-600 to-accent-700 p-6 flex flex-col">
                            <div class="flex items-center justify-between mb-8">
                                <div class="w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center">
                                    <i data-lucide="menu" class="w-4 h-4 text-white"></i>
                                </div>
                                <span class="text-white font-semibold text-sm">{{ $s->siteName }}</span>
                                <div class="w-8 h-8 bg-white/20 rounded-full"></div>
                            </div>

                            <div class="space-y-4">
                                @foreach($landingData->classes->take(3) as $class)
                                <div class="bg-white/10 backdrop-blur rounded-2xl p-4">
                                    <div class="flex items-center gap-3 mb-3">
                                        <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center">
                                            <i data-lucide="flame" class="w-5 h-5 text-white"></i>
                                        </div>
                                        <div>
                                            <div class="text-white font-medium text-sm">{{ $class->title }}</div>
                                            <div class="text-white/60 text-xs">{{ $class->durationMinutes }} {{ __('landing.minute_abbr') }}</div>
                                        </div>
                                    </div>
                                    <div class="flex gap-2">
                                        <span class="px-2 py-1 bg-white/20 rounded-lg text-white text-xs">{{ $class->categoryName }}</span>
                                        <span class="px-2 py-1 bg-white/20 rounded-lg text-white text-xs">{{ $class->durationMinutes }} {{ __('landing.minute_abbr') }}</span>
                                    </div>
                                </div>
                                @endforeach
                            </div>

                            <div class="mt-auto pt-6 flex justify-around">
                                <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center">
                                    <i data-lucide="home" class="w-5 h-5 text-white"></i>
                                </div>
                                <div class="w-10 h-10 bg-white/10 rounded-xl flex items-center justify-center">
                                    <i data-lucide="calendar" class="w-5 h-5 text-white/60"></i>
                                </div>
                                <div class="w-10 h-10 bg-white/10 rounded-xl flex items-center justify-center">
                                    <i data-lucide="user" class="w-5 h-5 text-white/60"></i>
                                </div>
                            </div>
                        </div>

                        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-32 h-7 bg-slate-800 dark:bg-slate-900 rounded-b-2xl"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="absolute bottom-8 left-1/2 -translate-x-1/2 animate-bounce">
        <a href="#features" class="flex flex-col items-center gap-2 text-slate-400 hover:text-primary-600 dark:hover:text-primary-400 transition-colors">
            <span class="text-xs font-medium">{{ __('landing.scroll_explore') }}</span>
            <i data-lucide="chevron-down" class="w-5 h-5"></i>
        </a>
    </div>
</section>
