{{--
    Locale switcher for the Operations Hub.

    Languages come from the `languages` table rather than a hardcoded en/ar pair, so the list
    stays in step with SetLocaleMiddleware — which only honours a locale that exists and is
    active. Selection persists in the session via GET /locale/{code}, which redirects back.

    Built on <details> and vanilla JS: this layout loads Tailwind and SweetAlert from a CDN and
    its own plain scripts, with no Alpine, so an x-data dropdown would simply never open.
--}}
@php
    $languages = app(\App\Services\Language\LanguageService::class)->getActiveLanguages();
    $currentLocale = app()->getLocale();
@endphp

@if ($languages->count() > 1)
    <details class="relative" data-language-switcher>
        <summary
            class="list-none cursor-pointer flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors text-sm font-medium select-none"
            title="{{ __('dashboard.operations_ui.header.language') }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129" />
            </svg>
            <span class="uppercase">{{ $currentLocale }}</span>
        </summary>

        <div
            class="absolute end-0 mt-2 w-44 rounded-xl glass-card border border-slate-200 dark:border-slate-800 shadow-xl overflow-hidden z-50">
            @foreach ($languages as $language)
                <a href="{{ route('locale.switch', $language->code) }}"
                    class="flex items-center justify-between gap-2 px-4 py-2.5 text-sm transition-colors
                        {{ $language->code === $currentLocale
                            ? 'bg-primary-50 dark:bg-primary-900/20 text-primary-600 font-bold'
                            : 'hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                    <span>{{ $language->name }}</span>
                    @if ($language->code === $currentLocale)
                        <span aria-hidden="true">&check;</span>
                    @endif
                </a>
            @endforeach
        </div>
    </details>

    <script>
        // <details> stays open on outside click on its own, so close it by hand.
        document.addEventListener('click', function (event) {
            document.querySelectorAll('details[data-language-switcher][open]').forEach(function (el) {
                if (!el.contains(event.target)) el.removeAttribute('open');
            });
        });
    </script>
@endif
