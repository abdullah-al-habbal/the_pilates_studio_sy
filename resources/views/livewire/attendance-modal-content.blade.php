<!-- resources\views\livewire\attendance-modal-content.blade.php -->
<div class="space-y-6" x-data="{ tab: 'attendees', walkInMode: @entangle('walkInMode') }">
    <!-- Tabs -->
    <div class="flex rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
        <button @click="tab = 'attendees'"
                :class="tab === 'attendees' ? 'bg-primary-600 text-white' : 'bg-white dark:bg-gray-900 text-gray-600 dark:text-gray-400 hover:bg-gray-50'"
                class="flex-1 px-4 py-2.5 text-sm font-medium flex items-center justify-center gap-2 transition-colors">
            <x-heroicon-o-users style="width:1rem;height:1rem;" />
            {{ __('dashboard.pages.scheduler.modal.confirmed_attendees') }} ({{ $attendedCount }})
        </button>
        <button @click="tab = 'walkin'"
                :class="tab === 'walkin' ? 'bg-primary-600 text-white' : 'bg-white dark:bg-gray-900 text-gray-600 dark:text-gray-400 hover:bg-gray-50'"
                class="flex-1 px-4 py-2.5 text-sm font-medium flex items-center justify-center gap-2 transition-colors">
            <x-heroicon-o-user-plus style="width:1rem;height:1rem;" />
            {{ __('dashboard.pages.scheduler.modal.add_walkin') }}
        </button>
    </div>

    <!-- Attendees Tab -->
    <div x-show="tab === 'attendees'" x-cloak>
        @if($bookings->isEmpty())
            <div class="flex flex-col items-center py-10 border border-dashed rounded-xl bg-gray-50 dark:bg-gray-800 border-gray-300 dark:border-gray-700">
                <x-heroicon-o-users style="width:2rem;height:2rem;" class="text-gray-300 dark:text-gray-600" />
                <p class="text-sm text-gray-500">{{ __('dashboard.pages.scheduler.modal.no_reservations') }}</p>
            </div>
        @else
            <div class="divide-y divide-gray-100 dark:divide-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
                @foreach($bookings as $bookingSession)
                    @php
        $user = $bookingSession->booking?->user;
        $activeBooking = $user?->bookings->first();
        $credits = $activeBooking?->remaining_credits ?? 0;
        $hasCredits = $credits > 0;
                    @endphp
                    <div class="flex items-center justify-between px-4 py-3 bg-white dark:bg-gray-900 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-9 h-9 rounded-full bg-primary-100 dark:bg-primary-900 flex items-center justify-center text-primary-700 dark:text-primary-300 font-bold text-sm shrink-0">
                                {{ substr($user?->fullname ?? '?', 0, 1) }}
                            </div>
                            <div class="min-w-0">
                                <p class="font-medium text-sm text-gray-900 dark:text-gray-100 truncate">{{ $user?->fullname ?? '—' }}</p>
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
                            {{ $this->markAttendedAction($bookingSession->id) }}
                            {{ $this->markMissedAction($bookingSession->id) }}
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Walk-in Tab -->
    <div x-show="tab === 'walkin'" x-cloak class="space-y-4">
        @if($isFull)
            <div class="flex items-center gap-3 p-4 rounded-xl bg-danger-50 dark:bg-danger-900/30 border border-danger-200 dark:border-danger-700 text-danger-700 dark:text-danger-300">
                <x-heroicon-o-no-symbol style="width:1.25rem;height:1.25rem;" />
                <p class="text-sm font-medium">{{ __('dashboard.pages.scheduler.session_full_notice') }}</p>
            </div>
        @else
            <div class="flex p-1 bg-gray-100 dark:bg-gray-800 rounded-lg">
                <button @click="walkInMode = 'existing'"
                        :class="walkInMode === 'existing' ? 'bg-white dark:bg-gray-700 shadow text-gray-900 dark:text-white' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'"
                        class="flex-1 px-4 py-2 text-sm font-medium rounded-md transition-all">
                    {{ __('dashboard.pages.scheduler.modal.existing_member') }}
                </button>
                <button @click="walkInMode = 'new'"
                        :class="walkInMode === 'new' ? 'bg-white dark:bg-gray-700 shadow text-gray-900 dark:text-white' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'"
                        class="flex-1 px-4 py-2 text-sm font-medium rounded-md transition-all">
                    {{ __('dashboard.pages.scheduler.modal.new_member') }}
                </button>
            </div>

            <div x-show="walkInMode === 'existing'">
                {{ $this->addWalkInAction() }}
            </div>
            <div x-show="walkInMode === 'new'">
                {{ $this->createAndAttendAction() }}
            </div>
        @endif
    </div>
</div>
