@php
    $currentLocale = session('spatie_translatable_active_locale', app()->getLocale());
    $locales = ['en', 'ar'];
@endphp
<div x-data="{ open: false }" class="relative">
    <button @click="open = !open" type="button"
        class="fi-icon-btn flex items-center gap-x-2 px-3 py-1.5 text-sm font-medium text-gray-600 dark:text-gray-300 hover:text-gray-800 dark:hover:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition">
        <span>{{ strtoupper($currentLocale) }}</span>
        <x-filament::icon
            icon="heroicon-m-chevron-down"
            class="w-4 h-4"
        />
    </button>
    <div x-show="open" @click.away="open = false" @keydown.escape.window="open = false"
        class="absolute left-0 mt-1 w-24 rounded-lg bg-white dark:bg-gray-800 shadow-lg ring-1 ring-gray-950/5 dark:ring-white/10 z-50 py-1 text-sm"
        style="display: none;"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="transform opacity-0 scale-95"
        x-transition:enter-end="transform opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="transform opacity-100 scale-100"
        x-transition:leave-end="transform opacity-0 scale-95">
        @foreach($locales as $locale)
            <a href="/locale/{{ $locale }}"
                class="block px-4 py-2 {{ $locale === $currentLocale ? 'text-primary-600 dark:text-primary-400 font-semibold' : 'text-gray-700 dark:text-gray-300' }} hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                {{ strtoupper($locale) }}
            </a>
        @endforeach
    </div>
</div>
