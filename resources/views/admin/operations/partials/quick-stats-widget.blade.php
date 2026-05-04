{{-- resources/views/admin/operations/partials/quick-stats-widget.blade.php --}}
<div id="quick-stats-container" class="glass-card rounded-2xl p-5 border-l-4 border-primary-500">
    <h3 class="text-xs font-bold text-primary-600 dark:text-primary-400 uppercase mb-3">Daily Snapshot</h3>
    <div class="space-y-3">
        <div class="flex justify-between items-center">
            <span class="text-sm text-slate-500">Balance</span>
            <span id="stat-balance" class="font-bold text-slate-900 dark:text-white">...</span>
        </div>
        <div class="w-full bg-slate-200 dark:bg-slate-800 h-1.5 rounded-full overflow-hidden">
            <div id="balance-progress" class="bg-primary-500 h-full w-0 transition-all duration-1000"></div>
        </div>
        <div class="flex justify-between items-center text-xs">
            <span class="text-slate-400">Daily Goal</span>
            <span id="stat-percentage" class="text-primary-600 font-medium">0%</span>
        </div>
    </div>
</div>