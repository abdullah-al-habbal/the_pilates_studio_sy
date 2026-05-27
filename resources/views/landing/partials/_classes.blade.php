@php $s = $landingData->settings; @endphp
<section id="classes" class="py-24 bg-white dark:bg-dark-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-6 mb-12 reveal">
            <div>
                <span class="inline-block px-4 py-1.5 bg-primary-50 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300 text-sm font-semibold rounded-full mb-4">{{ __('landing.our_classes') }}</span>
                <h2 class="text-3xl sm:text-4xl font-bold text-slate-900 dark:text-white">{{ $s->classesTitle }}</h2>
            </div>
            <a href="#download" class="inline-flex items-center gap-2 text-primary-600 dark:text-primary-400 font-semibold hover:gap-3 transition-all">
                {{ __('landing.view_full_schedule') }}
                <i data-lucide="arrow-right" class="w-5 h-5"></i>
            </a>
        </div>

        @if($landingData->classes->isEmpty())
            <div class="text-center py-12 text-slate-500 dark:text-slate-400">
                <i data-lucide="calendar-x" class="w-12 h-12 mx-auto mb-4 text-slate-300 dark:text-slate-600"></i>
                <p>{{ __('landing.empty_classes') }}</p>
            </div>
        @else
            @php
            $categories = $landingData->classes->pluck('categorySlug')->unique();
            @endphp
            <div class="flex flex-wrap gap-3 mb-10 reveal">
                <button class="filter-btn active px-5 py-2.5 rounded-full text-sm font-medium bg-primary-600 text-white transition-all" data-filter="all">{{ __('landing.all_classes') }}</button>
                @foreach($categories as $slug)
                    @php $cat = $landingData->classes->firstWhere('categorySlug', $slug); @endphp
                    <button class="filter-btn px-5 py-2.5 rounded-full text-sm font-medium bg-slate-100 dark:bg-dark-800 text-slate-600 dark:text-slate-300 hover:bg-primary-50 dark:hover:bg-primary-900/20 hover:text-primary-600 dark:hover:text-primary-400 transition-all" data-filter="{{ $slug }}">
                        {{ $cat?->categoryName ?? $slug }}
                    </button>
                @endforeach
            </div>

            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-8" id="classes-grid">
                @foreach($landingData->classes as $index => $class)
                <div class="class-card reveal group" data-category="{{ $class->categorySlug }}" style="transition-delay: {{ $index * 0.1 }}s;">
                    <div class="bg-white dark:bg-dark-800 rounded-2xl overflow-hidden border border-slate-100 dark:border-dark-700 hover-lift hover:border-primary-200 dark:hover:border-primary-800 transition-all">
                        <div class="relative h-56 overflow-hidden">
                            <img
                                src="{{ $class->imageUrl }}"
                                alt="{{ $class->title }}"
                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                loading="lazy"
                            >
                            <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                            <div class="absolute top-4 left-4">
                                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-teal-50 text-teal-700 dark:bg-teal-900/30 dark:text-teal-300">
                                    {{ $class->categoryName }}
                                </span>
                            </div>
                            <div class="absolute top-4 right-4">
                                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium bg-white/90 dark:bg-dark-900/90 text-slate-700 dark:text-slate-200 backdrop-blur">
                                    <i data-lucide="users" class="w-3 h-3"></i>
                                    {{ __('landing.spots_left', ['count' => $class->availableSpots]) }}
                                </span>
                            </div>
                        </div>

                        <div class="p-6">
                            <div class="flex items-start justify-between mb-3">
                                <h3 class="text-xl font-bold text-slate-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors">{{ $class->title }}</h3>
                            </div>

                            <div class="flex items-center gap-4 text-sm text-slate-500 dark:text-slate-400 mb-4">
                                <span class="flex items-center gap-1.5">
                                    <i data-lucide="user" class="w-4 h-4"></i>
                                    {{ $class->instructorName }}
                                </span>
                                <span class="flex items-center gap-1.5">
                                    <i data-lucide="clock" class="w-4 h-4"></i>
                                    {{ $class->durationMinutes }} {{ __('landing.minute_abbr') }}
                                </span>
                            </div>

                            <a href="#download" class="flex items-center justify-center gap-2 w-full py-3 rounded-xl bg-slate-50 dark:bg-dark-700 text-slate-700 dark:text-slate-200 font-semibold text-sm hover:bg-primary-50 dark:hover:bg-primary-900/20 hover:text-primary-600 dark:hover:text-primary-400 transition-all">
                                <i data-lucide="calendar-plus" class="w-4 h-4"></i>
                                {{ __('landing.view_schedule') }}
                            </a>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        @endif
    </div>
</section>
