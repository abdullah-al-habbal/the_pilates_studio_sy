@php $s = $landingData->settings; @endphp
<section id="testimonials" class="py-24 bg-slate-50 dark:bg-dark-800/50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto mb-16 reveal">
            <span class="inline-block px-4 py-1.5 bg-primary-50 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300 text-sm font-semibold rounded-full mb-4">{{ __('landing.testimonials') }}</span>
            <h2 class="text-3xl sm:text-4xl font-bold text-slate-900 dark:text-white mb-4">{{ $s->testimonialsTitle }}</h2>
        </div>

        @if($landingData->testimonials->isEmpty())
            <div class="text-center py-12 text-slate-500 dark:text-slate-400">
                <i data-lucide="message-circle" class="w-12 h-12 mx-auto mb-4 text-slate-300 dark:text-slate-600"></i>
                <p>{{ __('landing.empty_testimonials') }}</p>
            </div>
        @else
            <div class="grid md:grid-cols-3 gap-8">
                @foreach($landingData->testimonials as $index => $testimonial)
                <div class="reveal" style="transition-delay: {{ $index * 0.1 }}s;">
                    <div class="h-full bg-white dark:bg-dark-800 rounded-2xl p-8 border border-slate-100 dark:border-dark-700 hover-lift hover:border-primary-200 dark:hover:border-primary-800 transition-all relative">
                        <div class="absolute top-6 {{ app()->getLocale() === 'ar' ? 'left-6' : 'right-6' }} w-10 h-10 rounded-full bg-primary-50 dark:bg-primary-900/20 flex items-center justify-center">
                            <i data-lucide="quote" class="w-5 h-5 text-primary-400 dark:text-primary-600"></i>
                        </div>

                        <div class="flex gap-1 mb-6">
                            @for($i = 0; $i < $testimonial->rating; $i++)
                            <i data-lucide="star" class="w-5 h-5 text-yellow-400 fill-yellow-400"></i>
                            @endfor
                        </div>

                        <blockquote class="text-slate-600 dark:text-slate-300 leading-relaxed mb-8 text-sm">
                            "{{ $testimonial->quote }}"
                        </blockquote>

                        <div class="flex items-center gap-4 pt-6 border-t border-slate-100 dark:border-dark-700">
                            <img
                                src="{{ $testimonial->avatar }}"
                                alt="{{ $testimonial->name }}"
                                class="w-12 h-12 rounded-full object-cover"
                                loading="lazy"
                            >
                            <div>
                                <div class="font-bold text-slate-900 dark:text-white text-sm">{{ $testimonial->name }}</div>
                                <div class="text-xs text-slate-500 dark:text-slate-400">{{ $testimonial->role }}</div>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        @endif
    </div>
</section>
