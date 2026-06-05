{{-- /home/lenovo/work/projects/pilates/resources/views/landing/partials/_schedule.blade.php --}}
@php $s = $landingData->settings; $deepScheme = $s->deepLinkScheme ?? 'thepilatesstudio'; @endphp
<section id="schedule" class="py-24 bg-slate-50 dark:bg-dark-800/50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto mb-16 reveal">
            <span class="inline-block px-4 py-1.5 bg-primary-50 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300 text-sm font-semibold rounded-full mb-4">{{ __('landing.weekly_schedule') }}</span>
            <h2 class="text-3xl sm:text-4xl font-bold text-slate-900 dark:text-white mb-4">{{ $s->scheduleTitle }}</h2>
        </div>

        <div id="active-day-label" class="text-center mb-6 reveal">
            <span class="text-sm font-medium text-primary-600 dark:text-primary-400 bg-primary-50 dark:bg-primary-900/30 px-4 py-2 rounded-full">
                {{ __('landing.schedule_for', ['day' => __('landing.' . strtolower($landingData->schedule->first()?->dayName ?? 'mon'))]) }}
            </span>
        </div>

        <div class="flex overflow-x-auto gap-2 mb-8 pb-2 reveal" id="schedule-tabs">
            @foreach($landingData->schedule as $index => $day)
            @php
            $isFirst = ($index === 0);
            $tabClasses = $isFirst
                ? 'schedule-tab active bg-primary-600 text-white shadow-lg shadow-primary-500/25'
                : 'schedule-tab bg-white dark:bg-dark-800 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-dark-700 border border-slate-100 dark:border-dark-700';
            @endphp
            <button
                class="flex-shrink-0 px-6 py-3 rounded-xl text-sm font-semibold transition-all {{ $tabClasses }}"
                data-day="{{ $index }}"
            >
                <span class="block text-xs font-medium opacity-80">{{ __('landing.' . strtolower($day->dayName)) }}</span>
                <span class="block text-base">{{ \Carbon\Carbon::parse($day->date)->format('M j') }}</span>
                @if($day->count > 0)
                    <span class="block text-xs font-bold mt-1 opacity-80">{{ $day->count }} {{ Str::plural('class', $day->count) }}</span>
                @endif
            </button>
            @endforeach
        </div>

        <div class="space-y-4" id="schedule-content">
            @foreach($landingData->schedule as $dayIndex => $day)
            @php
            $dayDisplay = ($dayIndex === 0) ? 'block' : 'hidden';
            @endphp
            <div class="schedule-day {{ $dayDisplay }}" data-day-index="{{ $dayIndex }}">
                <div class="bg-white dark:bg-dark-800 rounded-2xl border border-slate-100 dark:border-dark-700 overflow-hidden">
                    @if($day->sessions->isEmpty())
                    <div class="p-6 text-center text-slate-500 dark:text-slate-400">
                        <i data-lucide="calendar-x" class="w-8 h-8 mx-auto mb-2 text-slate-300 dark:text-slate-600"></i>
                        <p>{{ __('landing.no_sessions_for_day') }}</p>
                    </div>
                    @else
                    @foreach($day->sessions as $sessionIndex => $session)
                    @php
                    $borderClass = ($sessionIndex > 0) ? 'border-t border-slate-100 dark:border-dark-700' : '';
                    $timeParts = explode(' ', $session->time);
                    $timeNum = $timeParts[0];
                    $timePeriod = $timeParts[1] ?? '';
                    @endphp
                    <div class="flex items-center gap-4 p-4 sm:p-6 {{ $borderClass }} hover:bg-slate-50 dark:hover:bg-dark-700/50 transition-colors group">
                        <div class="flex-shrink-0 w-20 sm:w-24 text-right">
                            <div class="text-sm font-bold text-slate-900 dark:text-white">{{ $timeNum }}</div>
                            <div class="text-xs text-slate-500 dark:text-slate-400">{{ $timePeriod }}</div>
                        </div>

                        <div class="flex-shrink-0 relative">
                            @php
                            $dotColor = 'bg-primary-500';
                            if ($session->isFull) {
                                $dotColor = 'bg-slate-300 dark:bg-slate-600';
                            } elseif ($session->availableSpots <= 3) {
                                $dotColor = 'bg-accent-500';
                            }
                            @endphp
                            <div class="w-3 h-3 rounded-full {{ $dotColor }} ring-4 ring-white dark:ring-dark-800"></div>
                        </div>

                        <div class="flex-1 min-w-0">
                            <div class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-4">
                                <h4 class="text-base font-bold text-slate-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors">{{ $session->className }}</h4>
                                <span class="text-xs text-slate-500 dark:text-slate-400 flex items-center gap-1">
                                    <i data-lucide="user" class="w-3 h-3"></i>
                                    {{ $session->instructorName }}
                                </span>
                                <span class="text-xs text-slate-500 dark:text-slate-400 flex items-center gap-1">
                                    <i data-lucide="clock" class="w-3 h-3"></i>
                                    {{ $session->durationMinutes }} {{ __('landing.minute_abbr') }}
                                </span>
                            </div>
                        </div>

                        <div class="flex items-center gap-4 flex-shrink-0">
                            @if($session->isFull)
                                <span class="hidden sm:inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium bg-slate-100 dark:bg-dark-700 text-slate-500 dark:text-slate-400">
                                    <i data-lucide="x-circle" class="w-3 h-3"></i>
                                    {{ __('landing.full') }}
                                </span>
                            @else
                                @if($session->availableSpots <= 3)
                                    <span class="hidden sm:inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium bg-accent-50 dark:bg-accent-900/20 text-accent-600 dark:text-accent-400 animate-pulse">
                                        <i data-lucide="alert-circle" class="w-3 h-3"></i>
                                        {{ __('landing.spots_left', ['count' => $session->availableSpots]) }}
                                    </span>
                                @else
                                    <span class="hidden sm:inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium bg-primary-50 dark:bg-primary-900/20 text-primary-600 dark:text-primary-400">
                                        <i data-lucide="users" class="w-3 h-3"></i>
                                        {{ $session->availableSpots }} {{ __('landing.nav_classes') }}
                                    </span>
                                @endif
                                <button onclick="handleDeepLink(event, '{{ $deepScheme }}', {{ $session->id }})" class="px-4 py-2 rounded-xl bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium transition-all hover:shadow-lg hover:shadow-primary-500/25">
                                    {{ __('landing.open_app') }}
                                </button>
                            @endif
                        </div>
                    </div>
                    @endforeach
                    @endif
                </div>
            </div>
            @endforeach
        </div>

        <div class="mt-8 text-center reveal">
            <p class="text-slate-500 dark:text-slate-400 mb-4">{{ __('landing.see_full_schedule') }}</p>
            <a href="#download" class="inline-flex items-center gap-2 text-primary-600 dark:text-primary-400 font-semibold hover:gap-3 transition-all">
                <i data-lucide="smartphone" class="w-5 h-5"></i>
                {{ __('landing.see_full_schedule') }}
                <i data-lucide="arrow-right" class="w-5 h-5"></i>
            </a>
        </div>
    </div>
</section>
