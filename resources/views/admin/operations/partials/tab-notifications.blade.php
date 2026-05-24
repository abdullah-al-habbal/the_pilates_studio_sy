<div class="space-y-6 animate-in fade-in slide-in-from-bottom-4 duration-500">
    <div class="space-y-1">
        <h2 class="text-2xl font-bold tracking-tight">Push Notifications</h2>
        <p class="text-slate-500">Compose and dispatch push notifications to users with registered FCM tokens.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- Compose Form --}}
        <div class="glass-card rounded-2xl p-6 space-y-5">
            <h3 class="text-lg font-bold flex items-center gap-2">
                <span class="text-2xl">🔔</span> Compose Notification
            </h3>

            <div class="space-y-1">
                <label class="text-xs font-bold text-slate-500 uppercase">Title</label>
                <input type="text" id="notif-title" maxlength="255" placeholder="e.g. New class available!"
                    class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 rounded-xl border-transparent
                           focus:ring-2 focus:ring-primary-500 outline-none text-sm">
                <p class="text-xs text-slate-400 text-right"><span id="notif-title-count">0</span>/255</p>
            </div>

            <div class="space-y-1">
                <label class="text-xs font-bold text-slate-500 uppercase">Body</label>
                <textarea id="notif-body" rows="4" maxlength="1000"
                    placeholder="Notification message content…"
                    class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 rounded-xl border-transparent
                           focus:ring-2 focus:ring-primary-500 outline-none text-sm resize-none"></textarea>
                <p class="text-xs text-slate-400 text-right"><span id="notif-body-count">0</span>/1000</p>
            </div>

            {{-- Target --}}
            <div class="space-y-2">
                <label class="text-xs font-bold text-slate-500 uppercase">Target Audience</label>
                <div class="flex gap-3">
                    <label class="flex-1 flex items-center gap-3 p-3 rounded-xl cursor-pointer border-2 border-transparent
                                  has-[:checked]:border-primary-500 has-[:checked]:bg-primary-50 dark:has-[:checked]:bg-primary-900/20
                                  bg-slate-50 dark:bg-slate-800 transition-all">
                        <input type="radio" name="notif-target" value="all" id="target-all" checked class="accent-primary-600">
                        <div>
                            <p class="text-sm font-bold">All Users</p>
                            <p class="text-xs text-slate-400">Everyone with an FCM token</p>
                        </div>
                    </label>
                    <label class="flex-1 flex items-center gap-3 p-3 rounded-xl cursor-pointer border-2 border-transparent
                                  has-[:checked]:border-primary-500 has-[:checked]:bg-primary-50 dark:has-[:checked]:bg-primary-900/20
                                  bg-slate-50 dark:bg-slate-800 transition-all">
                        <input type="radio" name="notif-target" value="specific" id="target-specific" class="accent-primary-600">
                        <div>
                            <p class="text-sm font-bold">Specific Users</p>
                            <p class="text-xs text-slate-400">Search and select below</p>
                        </div>
                    </label>
                </div>
            </div>

            {{-- User Search (shown when specific is selected) --}}
            <div id="notif-user-picker" class="space-y-3 hidden">
                <div class="relative">
                    <input type="text" id="notif-user-search" placeholder="Search by name or phone…"
                           class="w-full pl-10 pr-4 py-2.5 bg-slate-50 dark:bg-slate-800 rounded-xl
                                  border border-slate-200 dark:border-slate-700 focus:ring-2 focus:ring-primary-500 outline-none text-sm">
                    <svg class="w-4 h-4 absolute left-3 top-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                {{-- Scrollable results list --}}
                <div id="notif-user-results"
                     class="hidden max-h-56 overflow-y-auto rounded-xl border border-slate-200 dark:border-slate-700 
                            bg-white dark:bg-slate-900 shadow-sm divide-y divide-slate-100 dark:divide-slate-800">
                </div>
                {{-- Selected user tags --}}
                <div id="notif-selected-users" class="flex flex-wrap gap-2 min-h-[2rem]">
                    <p class="text-xs text-slate-400 italic">No users selected yet.</p>
                </div>
            </div>

            <button id="notif-send-btn" onclick="OperationsNotifications.send()"
                class="w-full bg-primary-600 hover:bg-primary-700 active:scale-[0.98] text-white
                       font-bold py-3 rounded-xl transition-all text-sm flex items-center justify-center gap-2 btn-single-action">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                </svg>
                Send Notification
            </button>
        </div>

        {{-- Results Panel --}}
        <div class="glass-card rounded-2xl p-6 space-y-4">
            <h3 class="text-lg font-bold flex items-center gap-2">
                <span class="text-2xl">📊</span> Dispatch Results
            </h3>
            <div id="notif-results-panel"
                class="h-64 flex items-center justify-center text-slate-400 text-sm text-center rounded-xl
                       bg-slate-50 dark:bg-slate-800 border border-dashed border-slate-200 dark:border-slate-700">
                <div>
                    <p class="text-3xl mb-2">📭</p>
                    <p>No dispatch yet. Send a notification to see results.</p>
                </div>
            </div>

            <div id="notif-history" class="space-y-2 max-h-64 overflow-y-auto"></div>
        </div>

    </div>
</div>