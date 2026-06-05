{{-- resources/views/admin/operations/partials/tab-approvals.blade.php --}}
<div class="space-y-6 animate-in fade-in slide-in-from-bottom-4 duration-500">
    <div class="space-y-2">
        <h2 class="text-2xl font-bold tracking-tight">Expense Approvals</h2>
        <p class="text-slate-500">Review and approve or reject pending expenses.</p>
    </div>

    <div id="approvals-container">
        <div class="glass-card rounded-2xl p-8 text-center text-slate-400">
            <div class="flex flex-col items-center gap-3">
                <svg class="w-8 h-8 animate-spin text-primary-500" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <span>Loading pending expenses...</span>
            </div>
        </div>
    </div>
</div>
