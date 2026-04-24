{{-- resources/views/admin/scheduler/partials/modal/attendees-tab.blade.php --}}
<div x-show="modal.tab === 'attendees'" x-cloak class="space-y-4">
    {{-- Capacity Insight --}}
    <div x-show="modal.session && modal.session.capacity > 0"
        class="bg-gray-50 dark:bg-gray-800/40 rounded-2xl p-4 border border-gray-100 dark:border-gray-800">
        <div
            class="flex items-center justify-between text-[10px] font-black uppercase tracking-widest text-gray-500 mb-2">
            <span>Live Engagement</span>
            <span class="text-primary-600 dark:text-primary-400"><span x-text="modal.session?.reserved ?? 0"></span> /
                <span x-text="modal.session?.capacity ?? 0"></span> Reserved</span>
        </div>
        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 relative overflow-hidden">
            <div class="h-full rounded-full transition-all duration-1000 ease-out"
                :class="modal.session?.is_full ? 'bg-danger-500' : 'bg-primary-500'"
                :style="'width:' + (modal.session?.capacity > 0 ? Math.min(100, Math.round((modal.session.reserved / modal.session.capacity) * 100)) : 0) + '%'">
            </div>
        </div>
    </div>
    <div x-show="modal.loading" class="space-y-3">
        <template x-for="i in [1,2,3]">
            <div class="h-16 bg-gray-50 dark:bg-gray-800/50 rounded-2xl animate-pulse"></div>
        </template>
    </div>
    <div x-show="!modal.loading && modal.bookings.length === 0"
        class="flex flex-col items-center py-16 gap-4 text-gray-400">
        <div
            class="w-16 h-16 bg-gray-50 dark:bg-gray-800/50 rounded-full flex items-center justify-center border border-gray-100 dark:border-gray-800">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0" />
            </svg>
        </div>
        <p class="text-sm font-bold text-gray-500">Waiting for reservations...</p>
    </div>
    <div x-show="!modal.loading && modal.bookings.length > 0" class="grid gap-3">
        <template x-for="b in modal.bookings" :key="b.id">
            @include('admin.scheduler.partials.modal.attendees-booking-card')
        </template>
    </div>
</div>