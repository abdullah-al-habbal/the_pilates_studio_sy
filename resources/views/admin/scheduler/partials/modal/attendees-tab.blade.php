{{-- resources/views/admin/scheduler/partials/modal/attendees-tab.blade.php --}}
<div id="tab-attendees" class="hidden space-y-4">
    <div id="capacity-bar-wrap"
        class="hidden bg-gray-50 dark:bg-gray-800/40 rounded-2xl p-4 border border-gray-100 dark:border-gray-800">
        <div
            class="flex items-center justify-between text-[10px] font-black uppercase tracking-widest text-gray-500 mb-2">
            <span>Live Engagement</span>
            <span class="text-primary-600 dark:text-primary-400"><span id="capacity-reserved">0</span> /
                <span id="capacity-total">0</span> Reserved</span>
        </div>
        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 relative overflow-hidden">
            <div id="capacity-bar-fill" class="h-full rounded-full transition-all duration-1000 ease-out bg-primary-500"
                style="width:0%">
            </div>
        </div>
    </div>

    <div id="attendees-skeleton" class="hidden space-y-3">
        @for ($i = 0; $i < 3; $i++)
            <div class="h-16 bg-gray-50 dark:bg-gray-800/50 rounded-2xl animate-pulse"></div>
        @endfor
    </div>

    <div id="attendees-empty" class="hidden flex flex-col items-center py-16 gap-4 text-gray-400">
        <div
            class="w-16 h-16 bg-gray-50 dark:bg-gray-800/50 rounded-full flex items-center justify-center border border-gray-100 dark:border-gray-800">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0" />
            </svg>
        </div>
        <p class="text-sm font-bold text-gray-500">Waiting for reservations...</p>
    </div>

    <div id="attendees-list" class="hidden grid gap-3"></div>
</div>