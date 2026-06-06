@php $s = $landingData->settings; @endphp
<section id="packages" class="py-24 bg-slate-50 dark:bg-dark-800/50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto mb-16 reveal">
            <span class="inline-block px-4 py-1.5 bg-primary-50 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300 text-sm font-semibold rounded-full mb-4">{{ __('landing.pricing') }}</span>
            <h2 class="text-3xl sm:text-4xl font-bold text-slate-900 dark:text-white mb-4">{{ $s->packagesTitle }}</h2>
            <p class="text-lg text-slate-600 dark:text-slate-300">{{ $s->packagesSubtitle }}</p>
        </div>

        @if($landingData->packages->isEmpty())
            <div class="text-center py-12 text-slate-500 dark:text-slate-400">
                <i data-lucide="credit-card" class="w-12 h-12 mx-auto mb-4 text-slate-300 dark:text-slate-600"></i>
                <p>{{ __('landing.empty_packages') }}</p>
            </div>
        @else
            <div class="grid md:grid-cols-3 gap-8 max-w-5xl mx-auto">
                @foreach($landingData->packages as $index => $package)
                <div class="reveal relative" style="transition-delay: {{ $index * 0.1 }}s;">
                    @if($index === 1)
                    <div class="absolute -top-4 left-1/2 -translate-x-1/2 z-10">
                        <span class="inline-flex items-center gap-1 px-4 py-1.5 bg-accent-500 text-white text-xs font-bold rounded-full shadow-lg">
                            <i data-lucide="star" class="w-3 h-3"></i>
                            {{ __('landing.most_popular') }}
                        </span>
                    </div>
                    @endif

                    <div class="h-full bg-white dark:bg-dark-800 rounded-2xl p-8 border-2 {{ $index === 1 ? 'border-primary-500 dark:border-primary-400 shadow-xl shadow-primary-500/10' : 'border-slate-100 dark:border-dark-700' }} hover-lift transition-all">
                        <div class="text-center mb-8">
                            <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-2">{{ $package->name }}</h3>
                            <div class="flex items-baseline justify-center gap-1">
                                <span class="text-4xl font-extrabold text-slate-900 dark:text-white">{{ $package->currency }} {{ number_format($package->price) }}</span>
                            </div>
                            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">{{ $package->credits }} {{ __('landing.credits_unit') }} · {{ $package->validityDays }} {{ __('landing.days_unit') }}</p>
                        </div>

                        <ul class="space-y-4 mb-8">
                            @foreach($package->features as $feature)
                            <li class="flex items-start gap-3">
                                <div class="flex-shrink-0 w-5 h-5 rounded-full bg-primary-50 dark:bg-primary-900/30 flex items-center justify-center mt-0.5">
                                    <i data-lucide="check" class="w-3 h-3 text-primary-600 dark:text-primary-400"></i>
                                </div>
                                <span class="text-sm text-slate-600 dark:text-slate-300">{{ $feature }}</span>
                            </li>
                            @endforeach
                        </ul>

                        <a href="#download" class="flex items-center justify-center gap-2 w-full py-3.5 rounded-xl {{ $index === 1 ? 'bg-primary-600 hover:bg-primary-700 text-white shadow-lg shadow-primary-500/25' : 'bg-slate-100 dark:bg-dark-700 text-slate-700 dark:text-slate-200 hover:bg-primary-50 dark:hover:bg-primary-900/20 hover:text-primary-600 dark:hover:text-primary-400' }} font-semibold transition-all">
                            <i data-lucide="download" class="w-4 h-4"></i>
                            {{ __('landing.get_in_app') }}
                        </a>
                    </div>
                </div>
                @endforeach
            </div>

            <div class="mt-12 text-center reveal">
                <div class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-dark-800 rounded-full border border-slate-200 dark:border-dark-700 text-sm text-slate-500 dark:text-slate-400">
                    <i data-lucide="globe" class="w-4 h-4"></i>
                    {{ __('landing.multi_currency', ['currency' => $landingData->packages->first()->currency]) }}
                </div>
            </div>
        @endif
    </div>
</section>
