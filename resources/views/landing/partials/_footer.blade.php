@php $s = $landingData->settings; @endphp
<footer class="bg-slate-900 dark:bg-black text-slate-300">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="py-16 grid sm:grid-cols-2 lg:grid-cols-4 gap-12">
            <div class="sm:col-span-2 lg:col-span-1">
                <a href="{{ route('landing') }}" class="flex items-center gap-2 mb-6">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-primary-500 to-teal-600 flex items-center justify-center">
                        <img src="{{ $s->logoUrl ?? '' }}" alt="{{ $s->siteName ?? '' }}" class="w-8 h-8 object-contain">
                    </div>
                    <span class="text-xl font-bold text-white">
                        {{ $s->siteName }}
                    </span>
                </a>
                <p class="text-sm text-slate-400 leading-relaxed mb-6">{{ $s->siteTagline }}</p>

                <div class="flex gap-3">
                    <a href="{{ $s->socialInstagram }}" class="w-10 h-10 rounded-xl bg-slate-800 hover:bg-primary-600 flex items-center justify-center transition-colors">
                        <i data-lucide="instagram" class="w-5 h-5"></i>
                    </a>
                    <a href="{{ $s->socialFacebook }}" class="w-10 h-10 rounded-xl bg-slate-800 hover:bg-primary-600 flex items-center justify-center transition-colors">
                        <i data-lucide="facebook" class="w-5 h-5"></i>
                    </a>
                    <a href="{{ $s->socialTwitter }}" class="w-10 h-10 rounded-xl bg-slate-800 hover:bg-primary-600 flex items-center justify-center transition-colors">
                        <i data-lucide="twitter" class="w-5 h-5"></i>
                    </a>
                    <a href="{{ $s->socialYoutube }}" class="w-10 h-10 rounded-xl bg-slate-800 hover:bg-primary-600 flex items-center justify-center transition-colors">
                        <i data-lucide="youtube" class="w-5 h-5"></i>
                    </a>
                </div>
            </div>

            <div>
                <h4 class="text-white font-semibold mb-6">{{ __('landing.explore') }}</h4>
                <ul class="space-y-3">
                    <li><a href="#classes" class="text-sm text-slate-400 hover:text-primary-400 transition-colors">{{ __('landing.nav_classes') }}</a></li>
                    <li><a href="#schedule" class="text-sm text-slate-400 hover:text-primary-400 transition-colors">{{ __('landing.nav_schedule') }}</a></li>
                    <li><a href="#instructors" class="text-sm text-slate-400 hover:text-primary-400 transition-colors">{{ __('landing.nav_instructors') }}</a></li>
                    <li><a href="#packages" class="text-sm text-slate-400 hover:text-primary-400 transition-colors">{{ __('landing.nav_pricing') }}</a></li>
                    <li><a href="#download" class="text-sm text-slate-400 hover:text-primary-400 transition-colors">{{ __('landing.nav_download_app') }}</a></li>
                </ul>
            </div>

            <div>
                <h4 class="text-white font-semibold mb-6">{{ __('landing.legal') }}</h4>
                <ul class="space-y-3">
                    @forelse($landingData->staticPages as $page)
                    <li><a href="{{ route('static-pages.show', $page->slug) }}" class="text-sm text-slate-400 hover:text-primary-400 transition-colors">{{ $page->getTranslation('title', app()->getLocale()) }}</a></li>
                    @empty
                    <li><span class="text-sm text-slate-500">-</span></li>
                    @endforelse
                </ul>
            </div>

            <div>
                <h4 class="text-white font-semibold mb-6">{{ __('landing.contact') }}</h4>
                <ul class="space-y-4">
                    <li class="flex items-start gap-3">
                        <i data-lucide="map-pin" class="w-5 h-5 text-primary-500 flex-shrink-0 mt-0.5"></i>
                        <span class="text-sm text-slate-400">{{ $s->contactAddress }}</span>
                    </li>
                    <li class="flex items-center gap-3">
                        <i data-lucide="phone" class="w-5 h-5 text-primary-500 flex-shrink-0"></i>
                        <span class="text-sm text-slate-400">{{ $s->contactPhone }}</span>
                    </li>
                    <li class="flex items-center gap-3">
                        <i data-lucide="mail" class="w-5 h-5 text-primary-500 flex-shrink-0"></i>
                        <span class="text-sm text-slate-400">{{ $s->contactEmail }}</span>
                    </li>
                    <li class="flex items-center gap-3">
                        <i data-lucide="clock" class="w-5 h-5 text-primary-500 flex-shrink-0"></i>
                        <span class="text-sm text-slate-400">{{ $s->openingHoursWeekdays }}<br>{{ $s->openingHoursWeekends }}</span>
                    </li>
                </ul>
            </div>
        </div>

        <div class="py-6 border-t border-slate-800 flex flex-col sm:flex-row items-center justify-between gap-4">
            <p class="text-sm text-slate-500">© {{ date('Y') }} {{ $s->footerCopyright }}. {{ __('landing.all_rights_reserved') }}</p>

            <div class="flex items-center gap-2">
                <i data-lucide="globe" class="w-4 h-4 text-slate-500"></i>
                <select class="bg-transparent text-sm text-slate-400 border-none focus:ring-0 cursor-pointer hover:text-slate-300 transition-colors" onchange="window.location.href='{{ url('locale') }}/' + this.value">
                    <option value="en" {{ app()->getLocale() === 'en' ? 'selected' : '' }}>{{ __('landing.english') }}</option>
                    <option value="ar" {{ app()->getLocale() === 'ar' ? 'selected' : '' }}>{{ __('landing.language') }}</option>
                </select>
            </div>
        </div>
    </div>
</footer>
