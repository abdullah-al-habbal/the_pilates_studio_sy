<!-- filePath: resources\views\components\scheduler\attendance-modal.blade.php -->
@props(['session', 'isFull' => false])
@php
    use App\Models\User;
    $bookings = $session->bookingSessions()->with([
        'booking.user.bookings' => fn ($q) =>
            $q->where('status', 'active')->where('remaining_credits', '>', 0),
    ])->get();

    $allUsers = User::orderBy('fullname')->get(['id', 'fullname', 'phone_number']);
@endphp

<div class="space-y-6 p-6" x-data="{
    tab: 'attendees',
    walkInMode: 'existing',
    userId: null,
    newUser: { fullname: '', phone_number: '', email: '', password: '' },
    loading: false,

    attend() {
        if (this.loading) return;
        this.loading = true;

        if (this.walkInMode === 'existing' && this.userId) {
            $wire.addWalkIn({{ $session->id }}, this.userId).then(() => {
                this.userId  = null;
                this.loading = false;
            });
        } else if (this.walkInMode === 'new' && this.newUser.fullname && this.newUser.phone_number) {
            $wire.createAndAttend({{ $session->id }}, this.newUser).then(() => {
                this.newUser = { fullname: '', phone_number: '', email: '', password: '' };
                this.loading = false;
            });
        } else {
            this.loading = false;
        }
    }
}">

    <div class="flex rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
        <button type="button"
            @click="tab = 'attendees'"
            :class="tab === 'attendees'
                ? 'bg-primary-600 text-white'
                : 'bg-white dark:bg-gray-900 text-gray-600 dark:text-gray-400 hover:bg-gray-50'"
            class="flex-1 px-4 py-2.5 text-sm font-medium flex items-center justify-center gap-2 transition-colors">
            <x-heroicon-o-users style="width:1rem;height:1rem;" />
            {{ __('dashboard.pages.scheduler.modal.confirmed_attendees') }}
            <span class="text-xs opacity-75">({{ $bookings->count() }})</span>
        </button>
        <button type="button"
            @click="tab = 'walkin'"
            :class="tab === 'walkin'
                ? 'bg-primary-600 text-white'
                : 'bg-white dark:bg-gray-900 text-gray-600 dark:text-gray-400 hover:bg-gray-50'"
            class="flex-1 px-4 py-2.5 text-sm font-medium flex items-center justify-center gap-2 transition-colors">
            <x-heroicon-o-user-plus style="width:1rem;height:1rem;" />
            {{ __('dashboard.pages.scheduler.modal.add_walkin') }}
        </button>
    </div>

    <div x-show="tab === 'attendees'" x-cloak>
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
                                    {{-- Credits badge --}}
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
    </div>

    <div x-show="tab === 'walkin'" x-cloak class="space-y-4">

        @if($isFull)
            <div class="flex items-center gap-3 p-4 rounded-xl bg-danger-50 dark:bg-danger-900/30 border border-danger-200 dark:border-danger-700 text-danger-700 dark:text-danger-300">
                <x-heroicon-o-no-symbol style="width:1.25rem;height:1.25rem;" class="shrink-0" />
                <p class="text-sm font-medium">{{ __('dashboard.pages.scheduler.session_full_notice') }}</p>
            </div>
        @else

            <div class="flex p-1 bg-gray-100 dark:bg-gray-800 rounded-lg">
                <button type="button"
                    @click="walkInMode = 'existing'"
                    :class="walkInMode === 'existing' 
                            ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow' 
                            : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'"
                    class="flex-1 px-4 py-2 text-sm font-medium rounded-md transition-all duration-200">
                    {{ __('dashboard.pages.scheduler.modal.existing_member') }}
                </button>
                <button type="button"
                    @click="walkInMode = 'new'"
                    :class="walkInMode === 'new' 
                            ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow' 
                            : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'"
                    class="flex-1 px-4 py-2 text-sm font-medium rounded-md transition-all duration-200">
                    {{ __('dashboard.pages.scheduler.modal.new_member') }}
                </button>
            </div>

            <div x-show="walkInMode === 'existing'" class="space-y-3">
                <select x-model="userId"
                    class="appearance-none block w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-sm text-gray-900 dark:text-gray-100 px-3 py-2 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                    style="background-image: url('data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' fill=\'none\' viewBox=\'0 0 20 20\'%3E%3Cpath stroke=\'%236b7280\' stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'1.5\' d=\'m6 8 4 4 4-4\'/%3E%3C/svg%3E'); background-position: right 0.5rem center; background-repeat: no-repeat;">
                    <option value="">{{ __('dashboard.pages.scheduler.modal.select_member') }}</option>
                    @foreach($allUsers as $user)
                        <option value="{{ $user->id }}">
                            {{ $user->fullname }} · {{ $user->phone_number }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div x-show="walkInMode === 'new'" class="space-y-3">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                            {{ __('dashboard.resources.users.fields.fullname') }} *
                        </label>
                        <input type="text" x-model="newUser.fullname"
                            class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm px-3 py-2 focus:border-primary-500 focus:ring-primary-500" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                            {{ __('dashboard.resources.users.fields.phone_number') }} *
                        </label>
                        <input type="tel" x-model="newUser.phone_number"
                            class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm px-3 py-2 focus:border-primary-500 focus:ring-primary-500" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                            {{ __('dashboard.resources.users.fields.email') }}
                        </label>
                        <input type="email" x-model="newUser.email"
                            class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm px-3 py-2 focus:border-primary-500 focus:ring-primary-500" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                            {{ __('dashboard.resources.users.fields.password') }}
                        </label>
                        <input type="password" x-model="newUser.password"
                            :placeholder="'{{ __('dashboard.resources.users.helpers.password_default') }}'"
                            class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm px-3 py-2 focus:border-primary-500 focus:ring-primary-500" />
                    </div>
                </div>
            </div>

            <button type="button"
                @click="attend()"
                :disabled="loading ||
                    (walkInMode === 'existing' && !userId) ||
                    (walkInMode === 'new' && (!newUser.fullname || !newUser.phone_number))"
                class="w-full flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg text-sm font-medium
                       bg-primary-600 text-white hover:bg-primary-700
                       disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                <template x-if="loading">
                    <x-heroicon-o-arrow-path style="width:1rem;height:1rem;" class="animate-spin" />
                </template>
                <template x-if="!loading">
                    <x-heroicon-o-user-plus style="width:1rem;height:1rem;" />
                </template>
                {{ __('dashboard.pages.scheduler.modal.attend_now') }}
            </button>

            <p class="text-xs text-gray-400 text-center">
                {{ __('dashboard.pages.scheduler.modal.note') }}
            </p>
        @endif
    </div>

</div>