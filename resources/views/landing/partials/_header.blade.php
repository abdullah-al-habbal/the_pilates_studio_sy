@php $settings = $landingData->settings; @endphp
<header id="main-header" class="fixed top-0 left-0 right-0 z-50 transition-all duration-300 bg-transparent">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-20">
            <a href="{{ route('landing') }}" class="flex items-center gap-2 group">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-primary-500 to-accent-600 flex items-center justify-center shadow-lg group-hover:shadow-primary-500/30 transition-shadow">
                    <img src="{{ $settings->logoUrl ?? '' }}" alt="{{ $settings->siteName ?? '' }}" class="w-8 h-8 object-contain">
                </div>
                <span class="text-xl font-bold tracking-tight text-slate-900 dark:text-white">
                    {{ $settings->siteName }}
                </span>
            </a>

            <nav class="hidden md:flex items-center gap-8">
                <a href="#classes" class="text-sm font-medium text-slate-600 dark:text-slate-300 hover:text-primary-600 dark:hover:text-primary-400 transition-colors">{{ __('landing.nav_classes') }}</a>
                <a href="#schedule" class="text-sm font-medium text-slate-600 dark:text-slate-300 hover:text-primary-600 dark:hover:text-primary-400 transition-colors">{{ __('landing.nav_schedule') }}</a>
                <a href="#instructors" class="text-sm font-medium text-slate-600 dark:text-slate-300 hover:text-primary-600 dark:hover:text-primary-400 transition-colors">{{ __('landing.nav_instructors') }}</a>
                <a href="#packages" class="text-sm font-medium text-slate-600 dark:text-slate-300 hover:text-primary-600 dark:hover:text-primary-400 transition-colors">{{ __('landing.nav_pricing') }}</a>
                <a href="#download" class="text-sm font-medium text-slate-600 dark:text-slate-300 hover:text-primary-600 dark:hover:text-primary-400 transition-colors">{{ __('landing.nav_download_app') }}</a>
            </nav>

            <div class="hidden md:flex items-center gap-4">
                <div class="relative" id="lang-switcher">
                    <button onclick="toggleLangDropdown()" class="flex items-center gap-1 px-3 py-2 rounded-lg hover:bg-slate-100 dark:hover:bg-dark-700 text-sm font-medium text-slate-600 dark:text-slate-300 transition-colors">
                        <i data-lucide="globe" class="w-4 h-4"></i>
                        <span>{{ app()->getLocale() === 'ar' ? 'العربية' : 'English' }}</span>
                        <i data-lucide="chevron-down" class="w-4 h-4"></i>
                    </button>
                    <div id="lang-dropdown" class="hidden absolute right-0 mt-2 w-40 bg-white dark:bg-dark-800 rounded-xl shadow-lg border border-slate-100 dark:border-dark-700 overflow-hidden z-50">
                        <a href="{{ url('locale/en') }}" class="block px-4 py-2 text-sm text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-dark-700 {{ app()->getLocale() === 'en' ? 'bg-primary-50 dark:bg-primary-900/20 text-primary-600' : '' }}">English</a>
                        <a href="{{ url('locale/ar') }}" class="block px-4 py-2 text-sm text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-dark-700 {{ app()->getLocale() === 'ar' ? 'bg-primary-50 dark:bg-primary-900/20 text-primary-600' : '' }}">العربية</a>
                    </div>
                </div>
                <button id="dark-toggle" class="p-2 rounded-lg hover:bg-slate-100 dark:hover:bg-dark-700 transition-colors" aria-label="Toggle dark mode">
                    <i data-lucide="moon" class="w-5 h-5 text-slate-600 dark:text-slate-300 hidden dark:block"></i>
                    <i data-lucide="sun" class="w-5 h-5 text-slate-600 dark:text-slate-300 block dark:hidden"></i>
                </button>
                <a href="#download" class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary-600 hover:bg-primary-700 text-white text-sm font-semibold rounded-xl transition-all hover:shadow-lg hover:shadow-primary-500/25">
                    <i data-lucide="calendar" class="w-4 h-4"></i>
                    {{ __('landing.book_class') }}
                </a>
            </div>

            <div class="flex md:hidden items-center gap-3">
                <button id="dark-toggle-mobile" class="p-2 rounded-lg hover:bg-slate-100 dark:hover:bg-dark-700 transition-colors" aria-label="Toggle dark mode">
                    <i data-lucide="moon" class="w-5 h-5 text-slate-600 dark:text-slate-300 hidden dark:block"></i>
                    <i data-lucide="sun" class="w-5 h-5 text-slate-600 dark:text-slate-300 block dark:hidden"></i>
                </button>
                <button id="mobile-menu-btn" class="p-2 rounded-lg hover:bg-slate-100 dark:hover:bg-dark-700 transition-colors" aria-label="Open menu">
                    <i data-lucide="menu" class="w-6 h-6 text-slate-700 dark:text-slate-200"></i>
                </button>
            </div>
        </div>
    </div>

    <div id="mobile-menu" class="hidden md:hidden glass border-t border-slate-200/50 dark:border-slate-700/50">
        <div class="px-4 py-6 space-y-4">
            <a href="#classes" class="block text-base font-medium text-slate-700 dark:text-slate-200 hover:text-primary-600 dark:hover:text-primary-400 transition-colors">{{ __('landing.nav_classes') }}</a>
            <a href="#schedule" class="block text-base font-medium text-slate-700 dark:text-slate-200 hover:text-primary-600 dark:hover:text-primary-400 transition-colors">{{ __('landing.nav_schedule') }}</a>
            <a href="#instructors" class="block text-base font-medium text-slate-700 dark:text-slate-200 hover:text-primary-600 dark:hover:text-primary-400 transition-colors">{{ __('landing.nav_instructors') }}</a>
            <a href="#packages" class="block text-base font-medium text-slate-700 dark:text-slate-200 hover:text-primary-600 dark:hover:text-primary-400 transition-colors">{{ __('landing.nav_pricing') }}</a>
            <a href="#download" class="block text-base font-medium text-slate-700 dark:text-slate-200 hover:text-primary-600 dark:hover:text-primary-400 transition-colors">{{ __('landing.nav_download_app') }}</a>
            <div class="pt-4 border-t border-slate-200/50 dark:border-slate-700/50">
                <div class="flex gap-4 mb-4">
                    <a href="{{ url('locale/en') }}" class="text-sm font-medium {{ app()->getLocale() === 'en' ? 'text-primary-600' : 'text-slate-600 dark:text-slate-300' }}">English</a>
                    <a href="{{ url('locale/ar') }}" class="text-sm font-medium {{ app()->getLocale() === 'ar' ? 'text-primary-600' : 'text-slate-600 dark:text-slate-300' }}">العربية</a>
                </div>
                <a href="#download" class="flex items-center justify-center gap-2 w-full px-5 py-3 bg-primary-600 hover:bg-primary-700 text-white text-sm font-semibold rounded-xl transition-all">
                    <i data-lucide="calendar" class="w-4 h-4"></i>
                    {{ __('landing.book_class') }}
                </a>
            </div>
        </div>
    </div>
</header>
