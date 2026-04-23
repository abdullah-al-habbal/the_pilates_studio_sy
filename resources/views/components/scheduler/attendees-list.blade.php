@props(['bookings'])

@if($bookings->isEmpty())
    <div class="flex flex-col items-center justify-center py-10 gap-3 rounded-xl border border-dashed border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
        <x-heroicon-o-users style="width:2rem;height:2rem;" class="text-gray-300 dark:text-gray-600" />
        <p class="text-sm text-gray-500">{{ __('dashboard.pages.scheduler.modal.no_reservations') }}</p>
    </div>
@else
    <div class="divide-y divide-gray-100 dark:divide-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
        @foreach($bookings as $bookingSession)
            @php
                $user            = $bookingSession->booking?->user;
                $activeBooking   = $user?->bookings->first();
                $credits         = $activeBooking?->remaining_credits ?? 0;
                $hasCredits      = $credits > 0;
                $currentStatus   = $bookingSession->attendance_status?->value ?? 'missed';
            @endphp

            <div class="flex items-center justify-between px-4 py-3 bg-white dark:bg-gray-900 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
                x-data="{ status: '{{ $currentStatus }}' }">

                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-9 h-9 rounded-full bg-primary-100 dark:bg-primary-900 flex items-center justify-center text-primary-700 dark:text-primary-300 font-bold text-sm shrink-0">
                        {{ substr($user?->fullname ?? '?', 0, 1) }}
                    </div>
                    <div class="min-w-0">
                        <p class="font-medium text-sm text-gray-900 dark:text-gray-100 truncate">
                            {{ $user?->fullname ?? '—' }}
                        </p>
                        <div class="flex items-center gap-2 mt-0.5">
                            <p class="text-xs text-gray-500 truncate">{{ $user?->phone_number }}</p>
                            @if($hasCredits)
                                <span class="inline-flex items-center gap-1 text-xs bg-success-100 dark:bg-success-900 text-success-700 dark:text-success-300 px-1.5 py-0.5 rounded-full font-medium">
                                    <x-heroicon-o-ticket style="width:.7rem;height:.7rem;" />
                                    {{ $credits }} {{ __('dashboard.pages.scheduler.credits_remaining') }}
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 text-xs bg-gray-100 dark:bg-gray-700 text-gray-500 px-1.5 py-0.5 rounded-full">
                                    {{ __('dashboard.pages.scheduler.no_credits') }}
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-1.5 shrink-0">
                    <button type="button"
                        @click="$wire.toggleAttendance({{ $bookingSession->id }}, 'attended'); status = 'attended'"
                        :class="status === 'attended'
                            ? 'bg-success-600 text-white shadow-sm'
                            : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-success-50'"
                        class="px-3 py-1.5 rounded-md text-xs font-medium transition-all">
                        ✓ {{ __('dashboard.pages.scheduler.modal.attended') }}
                    </button>
                    <button type="button"
                        @click="$wire.toggleAttendance({{ $bookingSession->id }}, 'missed'); status = 'missed'"
                        :class="status === 'missed'
                            ? 'bg-danger-600 text-white shadow-sm'
                            : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-danger-50'"
                        class="px-3 py-1.5 rounded-md text-xs font-medium transition-all">
                        ✗ {{ __('dashboard.pages.scheduler.modal.missed') }}
                    </button>
                </div>
            </div>
        @endforeach
    </div>
@endif
