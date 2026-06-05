@php $s = $landingData->settings; @endphp
<section id="download" class="py-24 bg-white dark:bg-dark-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="reveal relative overflow-hidden rounded-3xl bg-gradient-to-br from-primary-600 via-primary-700 to-accent-700 p-12 sm:p-16 lg:p-20">
            <div class="absolute inset-0 opacity-10">
                <div class="absolute top-0 left-0 w-full h-full" style="background-image: radial-gradient(circle at 2px 2px, white 1px, transparent 0); background-size: 40px 40px;"></div>
            </div>
            <div class="absolute top-0 right-0 w-96 h-96 bg-white/10 rounded-full -translate-y-1/2 translate-x-1/4 blur-3xl"></div>
            <div class="absolute bottom-0 left-0 w-72 h-72 bg-accent-400/20 rounded-full translate-y-1/2 -translate-x-1/4 blur-3xl"></div>

            <div class="relative text-center max-w-3xl mx-auto">
                <h2 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-white mb-6">{{ $s->ctaTitle }}</h2>
                <p class="text-lg sm:text-xl text-white/80 mb-10 leading-relaxed">{{ __('landing.first_class_free', ['app' => $s->siteName]) }}</p>

                <div class="flex flex-col sm:flex-row items-center justify-center gap-4 mb-12">
                    <button class="inline-flex items-center gap-3 px-6 py-4 bg-white text-slate-900 rounded-xl font-semibold hover:bg-slate-50 transition-colors shadow-xl">
                        <svg class="w-8 h-8" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.8-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M13 3.5c.73-.83 1.21-1.98 1.08-3.11-1.05.05-2.31.7-3.06 1.55-.67.76-1.26 1.97-1.1 3.12 1.17.09 2.36-.66 3.08-1.56z"/>
                        </svg>
                        <div class="text-left">
                            <div class="text-xs text-slate-500">{{ __('landing.download_on_app_store') }}</div>
                        </div>
                    </button>
                    <button class="inline-flex items-center gap-3 px-6 py-4 bg-white text-slate-900 rounded-xl font-semibold hover:bg-slate-50 transition-colors shadow-xl">
                        <svg class="w-8 h-8" viewBox="0 0 24 24" fill="currentColor">
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
</section>
