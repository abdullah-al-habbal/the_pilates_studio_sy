@props(['session', 'bookings', 'allUsers', 'isFull'])

<div class="space-y-6" x-data="{
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
                this.userId = null;
                this.loading = false;
                $wire.loadSessions(); // refresh the page data after walk-in
            });
        } else if (this.walkInMode === 'new' && this.newUser.fullname && this.newUser.phone_number) {
            $wire.createAndAttend({{ $session->id }}, this.newUser).then(() => {
                this.newUser = { fullname: '', phone_number: '', email: '', password: '' };
                this.loading = false;
                $wire.loadSessions();
            });
        } else {
            this.loading = false;
        }
    }
}">
    
    <!-- Tab buttons -->
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

    <!-- Attendees tab -->
    <div x-show="tab === 'attendees'" x-cloak>
        <x-scheduler.attendees-list :bookings="$bookings" />
    </div>

    <!-- Walk-in tab -->
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
