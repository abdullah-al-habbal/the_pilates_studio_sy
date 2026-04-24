<!-- resources/views/admin/scheduler/partial/modal.blade.php -->
<div x-show="modal.show" x-cloak>
    {{-- Backdrop --}}
    <div x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-150"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-40 bg-gray-900/60 backdrop-blur-sm" @click="closeModal()"></div>

    {{-- Panel --}}
    <div x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-4 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100" x-transition:leave="ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-4 scale-95"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6 pointer-events-none">

        <div class="relative w-full max-w-2xl max-h-[90vh] flex flex-col pointer-events-auto
                    bg-white dark:bg-gray-900 rounded-3xl shadow-[0_25px_50px_-12px_rgba(0,0,0,0.5)]
                    ring-1 ring-gray-200 dark:ring-gray-800 overflow-hidden border border-white/10">
            @include('admin.scheduler.partials.modal.header')

            @include('admin.scheduler.partials.modal.tabs')

            <div class="flex-1 overflow-y-auto px-8 py-6 space-y-4">
                @include('admin.scheduler.partials.modal.toast')
                @include('admin.scheduler.partials.modal.attendees-tab')
                @include('admin.scheduler.partials.modal.walkin-tab')
            </div>
        </div>
    </div>
</div>