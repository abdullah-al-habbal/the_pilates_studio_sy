{{-- resources/views/admin/scheduler/partials/main/skeleton-loader.blade.php --}}
<div id="skeleton-loader" class="hidden space-y-4">
    @for ($i = 0; $i < 4; $i++)
        <div
            class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-700 p-5 flex items-center gap-4 relative overflow-hidden">
            <div
                class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent -translate-x-full animate-[shimmer_2s_infinite]">
            </div>

            <div class="w-16 h-12 bg-gray-100 dark:bg-gray-800 rounded-xl shrink-0"></div>
            <div class="w-px h-10 bg-gray-100 dark:bg-gray-800 rounded-full"></div>

            <div class="flex-1 space-y-2">
                <div class="h-4 bg-gray-100 dark:bg-gray-800 rounded-md w-1/3"></div>
                <div class="h-3 bg-gray-50 dark:bg-gray-800/50 rounded-md w-1/4"></div>
            </div>

            <div class="w-24 h-10 bg-gray-100 dark:bg-gray-800 rounded-xl hidden sm:block"></div>
            <div class="w-28 h-10 bg-gray-100 dark:bg-gray-800 rounded-xl"></div>
        </div>
    @endfor
</div>