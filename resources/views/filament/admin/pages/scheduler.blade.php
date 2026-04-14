<x-filament-panels::page>

    {{-- Date picker --}}
    <div class="flex items-center gap-4 text-sm text-gray-500 dark:text-gray-400 -mt-2 mb-4" 
         wire:ignore 
         x-data="{
             selectedDate: @entangle('selectedDate').live,
             availableDates: @entangle('availableDates'),
             fp: null,
             init() {
                 let self = this;
                 
                 let initializeFlatpickr = () => {
                     this.fp = flatpickr(this.$refs.picker, {
                         defaultDate: this.selectedDate,
                         enable: this.availableDates,
                         dateFormat: 'Y-m-d',
                         onDayCreate: function(dObj, dStr, fp, dayElem) {
                             if (!dayElem.dateObj) return;
                             
                             // Format local date Y-m-d safely
                             let year = dayElem.dateObj.getFullYear();
                             let month = String(dayElem.dateObj.getMonth() + 1).padStart(2, '0');
                             let day = String(dayElem.dateObj.getDate()).padStart(2, '0');
                             let formatted = `${year}-${month}-${day}`;
                             
                             if (!self.availableDates.includes(formatted)) {
                                 dayElem.style.color = '#ef4444'; // red-500
                                 dayElem.style.textDecoration = 'line-through';
                                 dayElem.style.opacity = '0.5';
                             } else {
                                 dayElem.style.fontWeight = 'bold';
                                 dayElem.style.color = '#10b981'; // success/primary
                             }
                         },
                         onChange: function(selectedDates, dateStr) {
                             self.selectedDate = dateStr;
                         }
                     });
                 };

                 if (typeof flatpickr === 'undefined') {
                     let style = document.createElement('link');
                     style.rel = 'stylesheet';
                     style.href = 'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css';
                     document.head.appendChild(style);

                     let script = document.createElement('script');
                     script.src = 'https://cdn.jsdelivr.net/npm/flatpickr';
                     script.onload = () => initializeFlatpickr();
                     document.head.appendChild(script);
                 } else {
                     initializeFlatpickr();
                 }
                 
                 this.$watch('selectedDate', (value) => {
                     if (this.fp && value !== this.fp.input.value) {
                         this.fp.setDate(value);
                     }
                 });
             }
         }">
        <div class="flex items-center gap-2 relative">
            <x-heroicon-o-calendar-days style="width:1.25rem;height:1.25rem;" />
            <input x-ref="picker" type="text" readonly placeholder="YYYY-MM-DD"
                   class="block rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm px-3 py-1.5 focus:border-primary-500 focus:ring-primary-500 shadow-sm transition-colors text-gray-900 dark:text-gray-100 font-bold cursor-pointer w-36 text-center" />
        </div>
        <button wire:click="goToToday" type="button"
                class="text-xs font-semibold text-primary-600 hover:text-primary-700 dark:text-primary-400 transition-colors">
            {{ __('dashboard.pages.scheduler.actions.today') }}
        </button>
    </div>

    @if($sessions->isEmpty())
        {{-- ── Empty state ── --}}
        <x-filament::section>
            <div class="flex flex-col items-center justify-center py-16 gap-4 text-gray-400 dark:text-gray-600">
                <x-heroicon-o-calendar-days style="width:3rem;height:3rem;" />
                <div class="text-center">
                    <p class="text-base font-semibold">
                        {{ __('dashboard.pages.scheduler.empty.title') }}
                    </p>
                    <p class="text-sm mt-1">
                        {{ __('dashboard.pages.scheduler.empty.description') }}
                    </p>
                </div>
            </div>
        </x-filament::section>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
            @foreach($sessions as $session)
                @php
                    $locale       = app()->getLocale();
                    $title        = $session->class?->title[$locale] ?? $session->class?->title['en'] ?? '—';
                    $instructor   = $session->class?->instructor?->fullname ?? __('dashboard.pages.scheduler.no_instructor');
                    $start        = substr($session->start_time, 0, 5);
                    $end          = substr($session->end_time, 0, 5);
                    $attended     = $session->bookingSessions->where('attendance_status.value', 'attended')->count();
                    $total        = $session->bookingSessions->count();
                    $capacity     = $session->total_spots ?? 0;
                    $isFull       = $capacity > 0 && $total >= $capacity;
                @endphp

                <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm flex flex-col overflow-hidden">

                    <div class="bg-primary-50 dark:bg-primary-950 px-5 py-4 border-b border-gray-200 dark:border-gray-700">
                        <p class="font-bold text-gray-900 dark:text-gray-100 text-base leading-tight">{{ $title }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5 flex items-center gap-1">
                            <x-heroicon-o-user style="width:.9rem;height:.9rem;" />
                            {{ $instructor }}
                        </p>
                    </div>

                    <div class="px-5 py-4 flex-1 space-y-3">
                        <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                            <x-heroicon-o-clock style="width:.9rem;height:.9rem;" />
                            <span>{{ $start }} – {{ $end }}</span>
                        </div>

                        <div class="flex items-center gap-2 text-sm">
                            <x-heroicon-o-users style="width:.9rem;height:.9rem;" class="text-gray-400" />
                            <span class="font-medium text-gray-700 dark:text-gray-300">
                                {{ $attended }} / {{ $total }}
                                @if($capacity > 0)
                                    <span class="text-gray-400">(cap {{ $capacity }})</span>
                                @endif
                            </span>
                            @if($isFull)
                                <span class="ml-auto text-xs bg-danger-100 text-danger-700 dark:bg-danger-900 dark:text-danger-300 px-2 py-0.5 rounded-full font-medium">
                                    {{ __('dashboard.pages.scheduler.session_full') }}
                                </span>
                            @endif
                        </div>

                        @if($capacity > 0)
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5">
                                <div class="h-1.5 rounded-full {{ $isFull ? 'bg-danger-500' : 'bg-primary-500' }}"
                                    style="width: {{ min(100, ($total / $capacity) * 100) }}%">
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="px-5 py-3 border-t border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/50">
                        <button
                            wire:click="openModal({{ $session->id }})"
                            @click="
                                $dispatch('open-modal', {
                                    id: 'attendance-{{ $session->id }}',
                                });
                            "
                            class="w-full flex items-center justify-center gap-2 px-4 py-2 rounded-lg text-sm font-medium
                                   bg-primary-600 text-white hover:bg-primary-700 transition-colors">
                            <x-heroicon-o-clipboard-document-check style="width:1rem;height:1rem;" />
                            {{ __('dashboard.pages.scheduler.attendance') }}
                        </button>
                    </div>
                </div>

                <x-filament::modal :id="'attendance-' . $session->id" width="2xl">
                    <x-slot name="heading">
                        {{ __('dashboard.pages.scheduler.modal.heading', ['class' => $title, 'date' => $session->date->format('M j')]) }}
                    </x-slot>

                    @include('filament.admin.pages.scheduler.attendance-modal', [
                        'session' => $session,
                        'isFull'  => $isFull,
                    ])

                    <x-slot name="footerActions">
                        <x-filament::button color="gray" x-on:click="$dispatch('close-modal', { id: 'attendance-{{ $session->id }}' })">
                            {{ __('dashboard.pages.scheduler.modal.close') }}
                        </x-filament::button>
                    </x-slot>
                </x-filament::modal>
            @endforeach
        </div>
    @endif

</x-filament-panels::page>