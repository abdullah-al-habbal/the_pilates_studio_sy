{{-- resources/views/admin/scheduler/partials/modal/tabs.blade.php --}}
<div class="flex px-8 pt-6 gap-2 shrink-0">
    <button id="tab-btn-attendees" type="button"
        class="flex-1 flex items-center justify-center gap-2.5 px-6 py-3.5 rounded-2xl text-sm font-black transition-all duration-300">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0" />
        </svg>
        <!-- fix: this always be zero, even there are attendees -->
        Attendees (<span id="attendees-count">0</span>)
    </button>
    <button id="tab-btn-walkin" type="button"
        class="flex-1 flex items-center justify-center gap-2.5 px-6 py-3.5 rounded-2xl text-sm font-black transition-all duration-300">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
        </svg>
        Add Walk-in
    </button>
</div>