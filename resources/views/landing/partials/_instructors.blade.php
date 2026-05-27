@php $s = $landingData->settings; @endphp
<section id="instructors" class="py-24 bg-white dark:bg-dark-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto mb-16 reveal">
            <span class="inline-block px-4 py-1.5 bg-primary-50 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300 text-sm font-semibold rounded-full mb-4">{{ __('landing.expert_team') }}</span>
            <h2 class="text-3xl sm:text-4xl font-bold text-slate-900 dark:text-white mb-4">{{ $s->instructorsTitle }}</h2>
        </div>

        @if($landingData->instructors->isEmpty())
            <div class="text-center py-12 text-slate-500 dark:text-slate-400">
                <i data-lucide="users" class="w-12 h-12 mx-auto mb-4 text-slate-300 dark:text-slate-600"></i>
                <p>{{ __('landing.empty_instructors') }}</p>
            </div>
        @else
            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-8">
                @foreach($landingData->instructors as $index => $instructor)
                <div class="reveal group" style="transition-delay: {{ $index * 0.1 }}s;">
                    <div class="bg-white dark:bg-dark-800 rounded-2xl overflow-hidden border border-slate-100 dark:border-dark-700 hover-lift hover:border-primary-200 dark:hover:border-primary-800 transition-all">
                        <div class="relative h-72 overflow-hidden">
                            <img
                                src="{{ $instructor->imageUrl }}"
                                alt="{{ $instructor->name }}"
                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                loading="lazy"
                            >
                            <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent"></div>
                            @if(!empty($instructor->socialLinks))
                            <div class="absolute bottom-4 left-4 right-4 flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity translate-y-2 group-hover:translate-y-0">
                                @foreach($instructor->socialLinks as $link)
                                @php $platform = is_string($link) ? $link : ($link['platform'] ?? 'globe'); $url = is_string($link) ? '#' : ($link['url'] ?? '#'); @endphp
                                <a href="{{ $url }}" target="_blank" rel="noopener" class="w-9 h-9 rounded-lg bg-white/20 backdrop-blur flex items-center justify-center hover:bg-white/40 transition-colors">
                                    <i data-lucide="{{ $platform === 'tiktok' ? 'music' : $platform }}" class="w-4 h-4 text-white"></i>
                                </a>
                                @endforeach
                            </div>
                            @endif
                        </div>
                        <div class="p-6">
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-1">{{ $instructor->name }}</h3>
                            <p class="text-sm text-primary-600 dark:text-primary-400 font-medium mb-3">{{ $instructor->title }}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mb-3 flex items-center gap-1">
                                <i data-lucide="target" class="w-3 h-3"></i>
                                {{ $instructor->specialty }}
                            </p>
                            <p class="text-sm text-slate-600 dark:text-slate-400 leading-relaxed">{{ $instructor->bio }}</p>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        @endif
    </div>
</section>
