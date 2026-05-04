// public\js\operations\main.js
document.addEventListener('DOMContentLoaded', () => {
    initTheme();
    initTabs();
    loadTab('clients');
    updateGlobalStats();
});

document.addEventListener('click', function(e) {
    const btn = e.target.closest('.btn-single-action');
    if (!btn || btn.disabled) return;
    btn.disabled = true;
    const originalText = btn.textContent;
    btn.innerHTML = '<span class="btn-spinner"></span>' + originalText;
    setTimeout(() => {
        if (btn.disabled && btn.innerHTML.includes('btn-spinner')) {
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    }, 5000);
});

function initTheme() {
    const toggle = document.getElementById('theme-toggle');
    if (!toggle) return;

    const stored = localStorage.getItem('operations-theme');
    if (stored === 'dark' || (!stored && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        document.documentElement.classList.add('dark');
    }

    toggle.addEventListener('click', () => {
        document.documentElement.classList.toggle('dark');
        localStorage.setItem(
            'operations-theme',
            document.documentElement.classList.contains('dark') ? 'dark' : 'light'
        );
    });
}

function initTabs() {
    const buttons = document.querySelectorAll('[data-tab]');
    buttons.forEach(btn => {
        btn.addEventListener('click', () => {
            const tab = btn.dataset.tab;
            loadTab(tab);
            buttons.forEach(b => {
                b.classList.remove('bg-primary-600', 'text-white', 'shadow-lg', 'shadow-primary-500/20', 'active-tab');
                b.classList.add('hover:bg-slate-100', 'dark:hover:bg-slate-800');
            });
            btn.classList.add('bg-primary-600', 'text-white', 'shadow-lg', 'shadow-primary-500/20', 'active-tab');
            btn.classList.remove('hover:bg-slate-100', 'dark:hover:bg-slate-800');
        });
    });
}

function loadTab(tab) {
    const container = document.getElementById('tab-content-container');
    const template  = document.getElementById(`tpl-${tab}`);
    if (!template) return;

    container.innerHTML = template.innerHTML;

    if (tab === 'clients') initClientsTab();
    if (tab === 'store')   initStoreTab();
    if (tab === 'finance') initFinanceTab();
}

async function updateGlobalStats() {
    try {
        const result = await OperationsAPI.getDailyBalance();
        OperationsUI.renderBalance(result.data);
    } catch (e) {
        console.error('Failed to load global stats:', e);
    }
}

function renderShimmerRows(tbodyId, colCount = 4, rowCount = 6) {
    const tbody = document.getElementById(tbodyId);
    if (!tbody) return;

    const widths  = ['w-50', 'w-30', 'w-20', 'w-15'];
    const rows = Array.from({ length: rowCount }, () => `
        <tr class="shimmer-row border-b border-slate-100 dark:border-slate-800/50">
            ${Array.from({ length: colCount }, (_, i) => `
                <td><div class="shimmer-cell ${widths[i % widths.length]}"></div></td>
            `).join('')}
        </tr>`).join('');

    tbody.innerHTML = rows;
}

let clientSearchTimeout = null;

function initClientsTab() {
    const searchInput = document.getElementById('client-search');
    if (!searchInput) return;

    searchInput.addEventListener('input', (e) => {
        clearTimeout(clientSearchTimeout);
        clientSearchTimeout = setTimeout(() => renderClients(e.target.value), 300);
    });

    renderClients();
}

async function renderClients(search = '', page = 1) {
    renderShimmerRows('client-table-body', 6, 6);

    const tbody = document.getElementById('client-table-body');

    try {
        const result = await OperationsAPI.getClients(search, page);

        if (!result.data || result.data.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center py-12 text-slate-400">
                        No clients found.
                    </td>
                </tr>`;
            return;
        }

        tbody.innerHTML = result.data.map(user => `
            <tr class="border-b border-slate-100 dark:border-slate-800/50 hover:bg-slate-50/50 dark:hover:bg-slate-800/20 transition-colors">
                <td class="px-6 py-4 font-medium">${user.fullname}</td>
                <td class="px-6 py-4 text-slate-500">${user.phone_number}</td>
                <td class="px-6 py-4">
                    <span class="px-2 py-1 rounded-full text-xs font-bold ${user.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500'}">
                        ${user.is_active ? 'ACTIVE' : 'INACTIVE'}
                    </span>
                </td>
                <td class="px-6 py-4">
                    ${user.active_package ? `
                        <span class="text-sm font-medium">${user.active_package.name}</span>
                        <span class="text-xs text-slate-400 ml-1">(${user.active_package.remaining_credits}/${user.active_package.total_credits})</span>
                    ` : '<span class="text-xs text-slate-400">No package</span>'}
                </td>
                <td class="px-6 py-4">
                    <span class="text-sm font-medium">${user.sessions_attended}</span>
                    <span class="text-xs text-slate-400 ml-1">attended</span>
                </td>
                <td class="px-6 py-4 text-right">
                    <button onclick="showClientDetails(${user.id})"
                        class="text-primary-600 hover:text-primary-700 font-bold text-sm btn-single-action">
                        Details
                    </button>
                </td>
            </tr>`).join('');

        renderPagination(result.meta);

    } catch (e) {
        console.error('Failed to load clients:', e);
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="text-center py-12">
                    <div class="flex flex-col items-center gap-2">
                        <span class="text-rose-500 font-bold">Error loading clients</span>
                        <p class="text-xs text-slate-400">${e.message}</p>
                        <button onclick="renderClients()" class="text-xs text-primary-600 underline">Try again</button>
                    </div>
                </td>
            </tr>`;
        OperationsUI.toast('Failed to load clients', 'error');
    }
}

function renderPagination(meta) {
    const container = document.getElementById('client-pagination');
    if (!container || !meta || !meta.pagination) return;

    const p = meta.pagination;
    container.innerHTML = `
        <span class="text-xs text-slate-500 font-medium">
            Page ${p.current_page} of ${p.total_pages} (${p.total} clients)
        </span>
        <div class="flex gap-1">
            <button onclick="renderClients('', ${p.current_page - 1})"
                ${p.current_page === 1 ? 'disabled' : ''}
                class="px-3 py-1 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg disabled:opacity-50">&larr;</button>
            <button onclick="renderClients('', ${p.current_page + 1})"
                ${p.current_page === p.total_pages ? 'disabled' : ''}
                class="px-3 py-1 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg disabled:opacity-50">&rarr;</button>
        </div>`;
}

async function showClientDetails(userId) {
    OperationsUI.openModal('Client Workspace', `
        <div class="space-y-6">
            ${Array(3).fill('').map(() => `
                <div class="glass-card rounded-2xl p-6 space-y-3">
                    <div class="shimmer-cell w-30" style="height:12px;"></div>
                    <div class="shimmer-cell w-50" style="height:24px;border-radius:6px;"></div>
                    <div class="shimmer-cell w-20" style="height:12px;"></div>
                </div>`).join('')}
        </div>`);

    try {
        const result = await OperationsAPI.getClientDetails(userId);
        const user   = result.data;

        const content = `
            <div class="space-y-8">
                <!-- Header -->
                <div class="flex items-start justify-between">
                    <div class="flex gap-4 items-center">
                        <div class="w-16 h-16 bg-slate-100 dark:bg-slate-800 rounded-2xl flex items-center justify-center text-2xl font-bold text-slate-400">
                            ${user.fullname.charAt(0).toUpperCase()}
                        </div>
                        <div>
                            <h4 class="text-2xl font-bold">${user.fullname}</h4>
                            <p class="text-slate-500">${user.phone_number} &bull; Member since ${user.member_since}</p>
                        </div>
                    </div>
                    <button onclick="showPackageAssignment(${user.id})"
                        class="bg-primary-600 text-white px-5 py-2.5 rounded-xl font-bold text-sm hover:scale-105 transition-all btn-single-action">
                        + Assign Package
                    </button>
                </div>

                <!-- Package + Activity -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Current Package -->
                    <div class="glass-card rounded-2xl p-6 border-l-4 border-gold-500">
                        <h5 class="text-xs font-bold text-slate-400 uppercase mb-4">Current Package</h5>
                        ${user.active_package ? `
                            <div class="space-y-4">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <p class="text-xl font-bold">${user.active_package.name}</p>
                                        <p class="text-sm text-slate-500">Source: ${user.active_package.source_type}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-2xl font-black text-primary-600">
                                            ${user.active_package.remaining_credits} / ${user.active_package.total_credits}
                                        </p>
                                        <p class="text-xs font-bold text-slate-400">CREDITS</p>
                                    </div>
                                </div>
                                <div class="flex justify-between items-center pt-2">
                                    <span class="text-sm font-medium text-slate-600 dark:text-slate-400">
                                        Expires: ${user.active_package.expires_at || 'Never'}
                                    </span>
                                    ${user.active_package.remaining_days !== null ? `
                                        <span class="px-2 py-0.5 rounded-lg bg-gold-100 text-gold-700 text-xs font-bold">
                                            ${user.active_package.remaining_days} days left
                                        </span>` : ''}
                                </div>
                                <div class="flex gap-2 pt-2">
                                    ${user.active_package.status === 'frozen' ? `
                                        <button onclick="handleUnfreeze(${user.active_package.id}, ${user.id})"
                                            class="flex-1 bg-emerald-100 text-emerald-700 py-2 rounded-lg font-bold text-xs uppercase tracking-wider hover:bg-emerald-200 transition-colors btn-single-action">
                                            Unfreeze Now
                                        </button>` : `
                                        <button onclick="handleFreeze(${user.active_package.id}, ${user.id})"
                                            class="flex-1 bg-amber-100 text-amber-700 py-2 rounded-lg font-bold text-xs uppercase tracking-wider hover:bg-amber-200 transition-colors btn-single-action">
                                            Freeze Package
                                        </button>`}
                                </div>
                            </div>` : `
                            <div class="flex flex-col items-center py-6 text-center">
                                <p class="text-slate-400 font-medium">No active package found.</p>
                                <p class="text-xs text-slate-400 mt-1">Client needs a new subscription.</p>
                            </div>`}
                    </div>

                    <!-- Activity Snapshot -->
                    <div class="glass-card rounded-2xl p-6">
                        <h5 class="text-xs font-bold text-slate-400 uppercase mb-4">Activity Snapshot</h5>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="p-4 bg-slate-50 dark:bg-slate-800 rounded-xl">
                                <p class="text-2xl font-bold">${user.activity_snapshot.total_sessions_attended}</p>
                                <p class="text-xs text-slate-500 font-medium">Attended</p>
                            </div>
                            <div class="p-4 bg-slate-50 dark:bg-slate-800 rounded-xl">
                                <p class="text-2xl font-bold text-rose-500">${user.activity_snapshot.total_sessions_cancelled}</p>
                                <p class="text-xs text-slate-500 font-medium">Cancelled</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Store Purchases -->
                <div class="space-y-4">
                    <h5 class="text-xs font-bold text-slate-400 uppercase">Recent Store Purchases</h5>
                    <div class="border border-slate-100 dark:border-slate-800 rounded-xl overflow-hidden">
                        ${user.store_purchases && user.store_purchases.length > 0 ? `
                            <table class="w-full text-left text-sm">
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                    ${user.store_purchases.slice(0, 5).map(o => `
                                        <tr>
                                            <td class="px-4 py-3">${o.item_name}</td>
                                            <td class="px-4 py-3 text-slate-500">${o.quantity} unit(s)</td>
                                            <td class="px-4 py-3 font-bold">${OperationsUI.formatCurrency(o.total_price)}</td>
                                            <td class="px-4 py-3 text-right text-xs text-slate-400">${o.ordered_at}</td>
                                        </tr>`).join('')}
                                </tbody>
                            </table>` : `
                            <p class="p-6 text-center text-slate-400 italic">No purchase history.</p>`}
                    </div>
                </div>
            </div>`;

        OperationsUI.openModal('Client Workspace', content);

    } catch (e) {
        console.error('Failed to load client details:', e);
        OperationsUI.openModal('Client Workspace', `
            <div class="flex flex-col items-center py-16 gap-4">
                <svg class="w-12 h-12 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                </svg>
                <p class="text-rose-500 font-bold">Failed to load client details</p>
                <p class="text-sm text-slate-400">${e.message}</p>
                <button onclick="OperationsUI.closeModal()" class="px-4 py-2 bg-slate-100 dark:bg-slate-800 rounded-xl text-sm font-medium">Close</button>
            </div>`);
    }
}

async function showPackageAssignment(userId) {
    OperationsUI.openModal('Assign New Package', `
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            ${Array(4).fill('').map(() => `
                <div class="rounded-2xl border-2 border-slate-100 dark:border-slate-800 p-6 space-y-3">
                    <div class="shimmer-cell w-50" style="height:20px;border-radius:4px;"></div>
                    <div class="shimmer-cell w-30" style="height:14px;"></div>
                    <div class="shimmer-cell w-20" style="height:28px;border-radius:6px;margin-top:1rem;"></div>
                </div>`).join('')}
        </div>`);

    try {
        const result   = await OperationsAPI.getPackages();
        const packages = result.data;

        const content = `
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                ${packages.map(p => `
                    <button onclick="handlePackageAssign(${userId}, ${p.id})"
                        class="flex flex-col p-6 rounded-2xl border-2 border-slate-100 dark:border-slate-800 hover:border-primary-500 transition-all text-left group btn-single-action">
                        <span class="text-lg font-bold group-hover:text-primary-600 transition-colors">${p.name}</span>
                        <span class="text-sm text-slate-500">${p.total_credits} Sessions &bull; ${p.validity_days} Days</span>
                        <span class="mt-4 text-2xl font-black text-slate-900 dark:text-white">
                            ${OperationsUI.formatCurrency(p.price)}
                        </span>
                    </button>`).join('')}
            </div>`;

        OperationsUI.openModal('Assign New Package', content);

    } catch (e) {
        console.error('Failed to load packages:', e);
        OperationsUI.toast('Failed to load packages', 'error');
    }
}

async function handlePackageAssign(userId, packageId) {
    try {
        await OperationsAPI.assignPackage(userId, packageId);
        OperationsUI.toast('Package assigned successfully!', 'success');
        OperationsUI.closeModal();
        renderClients();
    } catch (e) {
        console.error('Failed to assign package:', e);
        OperationsUI.toast(e.message, 'error');
    }
}

async function handleFreeze(bookingId, userId) {
    if (!confirm('Freeze this package? Validity calculations will pause until unfrozen.')) return;
    try {
        await OperationsAPI.freezeBooking(bookingId);
        OperationsUI.toast('Package frozen successfully.', 'success');
        showClientDetails(userId);
    } catch (e) {
        OperationsUI.toast(e.message, 'error');
    }
}

async function handleUnfreeze(bookingId, userId) {
    if (!confirm('Unfreeze package? A new replacement booking will be created for the remaining validity.')) return;
    try {
        await OperationsAPI.unfreezeBooking(bookingId);
        OperationsUI.toast('Package unfrozen and resumed.', 'success');
        showClientDetails(userId);
    } catch (e) {
        OperationsUI.toast(e.message, 'error');
    }
}

function initStoreTab() {
    renderStore();
}

async function renderStore() {
    OperationsUI.renderStoreShimmer();

    try {
        const result = await OperationsAPI.getStoreItems();

        if (!result.data || result.data.length === 0) {
            document.getElementById('store-grid').innerHTML = `
                <div class="col-span-full py-12 text-center text-slate-400">
                    No merchandise available.
                </div>`;
            return;
        }

        document.getElementById('store-grid').innerHTML = result.data.map(item => `
            <div class="glass-card rounded-2xl p-6 space-y-4 hover:shadow-xl transition-all border-b-4 ${item.stock_quantity > 5 ? 'border-primary-500' : 'border-amber-500'}">
                <div class="flex justify-between items-start">
                    <div>
                        <span class="text-[10px] font-black uppercase tracking-widest text-primary-500">
                            ${item.category || 'Product'}
                        </span>
                        <h4 class="text-xl font-bold">${item.name}</h4>
                    </div>
                    <div class="text-right">
                        <span class="text-xl font-black">${OperationsUI.formatCurrency(item.price)}</span>
                    </div>
                </div>
                <div class="flex justify-between items-center text-sm">
                    <span class="text-slate-500">In Stock:</span>
                    <span class="font-bold ${item.stock_quantity <= 5 ? 'text-amber-500' : ''}">
                        ${item.stock_quantity}
                    </span>
                </div>
                <button
                    onclick="showQuickSale(${item.id}, '${item.name.replace(/'/g, "\\'")}')"
                    class="w-full bg-slate-900 dark:bg-white dark:text-slate-900 text-white py-3 rounded-xl font-bold text-sm hover:scale-[1.02] transition-all disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100 btn-single-action"
                    ${item.stock_quantity <= 0 ? 'disabled' : ''}>
                    ${item.stock_quantity <= 0 ? 'Out of Stock' : 'Quick Sale'}
                </button>
            </div>`).join('');

    } catch (e) {
        console.error('Failed to load store:', e);
        document.getElementById('store-grid').innerHTML = `
            <div class="col-span-full py-12 text-center space-y-3">
                <p class="text-rose-500 font-bold">Failed to load merchandise.</p>
                <p class="text-sm text-slate-400">${e.message}</p>
                <button onclick="renderStore()" class="text-xs text-primary-600 underline">Try again</button>
            </div>`;
        OperationsUI.toast('Failed to load store', 'error');
    }
}

async function showQuickSale(itemId, itemName) {
    const existingTab = () => `
        <div id="existing-customer-form" class="space-y-4">
            <div class="space-y-2">
                <label class="text-sm font-bold text-slate-600 dark:text-slate-400">Select Customer</label>
                <select name="customer_id" id="sale-customer-select"
                    class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 rounded-xl border-transparent focus:ring-2 focus:ring-primary-500 outline-none">
                    <option value="">Loading customers...</option>
                </select>
            </div>
        </div>`;

    const walkInTab = () => `
        <div id="walkin-customer-form" class="space-y-4 hidden">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="space-y-1">
                    <label class="text-xs font-bold text-slate-500 uppercase">Full Name <span class="text-rose-500">*</span></label>
                    <input type="text" id="walkin-fullname" placeholder="Jane Doe"
                        class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 rounded-xl border-transparent focus:ring-2 focus:ring-primary-500 outline-none">
                </div>
                <div class="space-y-1">
                    <label class="text-xs font-bold text-slate-500 uppercase">Phone <span class="text-rose-500">*</span></label>
                    <input type="text" id="walkin-phone" placeholder="+971..."
                        class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 rounded-xl border-transparent focus:ring-2 focus:ring-primary-500 outline-none">
                </div>
            </div>
            <div class="space-y-1">
                <label class="text-xs font-bold text-slate-500 uppercase">Email <span class="text-slate-400">(optional)</span></label>
                <input type="email" id="walkin-email" placeholder="jane@example.com"
                    class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 rounded-xl border-transparent focus:ring-2 focus:ring-primary-500 outline-none">
            </div>
        </div>`;

    const content = `
        <div class="space-y-6">
            <input type="hidden" id="sale-merchandise-id" value="${itemId}">

            <!-- Customer mode toggle -->
            <div class="flex rounded-xl overflow-hidden border border-slate-200 dark:border-slate-700 text-sm font-bold">
                <button id="btn-existing" onclick="toggleSaleMode('existing')"
                    class="flex-1 py-2.5 bg-primary-600 text-white transition-colors">
                    Existing Client
                </button>
                <button id="btn-walkin" onclick="toggleSaleMode('walkin')"
                    class="flex-1 py-2.5 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 transition-colors">
                    + Walk-in
                </button>
            </div>

            ${existingTab()}
            ${walkInTab()}

            <!-- Quantity -->
            <div class="space-y-2">
                <label class="text-sm font-bold text-slate-600 dark:text-slate-400">Quantity</label>
                <input type="number" id="sale-quantity" value="1" min="1"
                    class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 rounded-xl border-transparent focus:ring-2 focus:ring-primary-500 outline-none">
            </div>

            <button id="sale-submit-btn" onclick="submitQuickSale()"
                class="w-full bg-primary-600 text-white py-4 rounded-xl font-bold text-lg hover:shadow-lg hover:scale-[1.01] transition-all btn-single-action">
                Confirm Purchase
            </button>
        </div>`;

    OperationsUI.openModal(`Sale: ${itemName}`, content);

    try {
        const result = await OperationsAPI.getClients('', 1);
        const select = document.getElementById('sale-customer-select');
        if (select) {
            select.innerHTML = '<option value="">-- Choose Client --</option>' +
                result.data.map(u => `<option value="${u.id}">${u.fullname} (${u.phone_number})</option>`).join('');
        }
    } catch (e) {
        console.error('Failed to load customers for sale:', e);
        const select = document.getElementById('sale-customer-select');
        if (select) select.innerHTML = '<option value="">Failed to load clients</option>';
        OperationsUI.toast('Failed to load customers', 'error');
    }
}

function toggleSaleMode(mode) {
    const isExisting = mode === 'existing';

    document.getElementById('existing-customer-form').classList.toggle('hidden', !isExisting);
    document.getElementById('walkin-customer-form').classList.toggle('hidden', isExisting);

    document.getElementById('btn-existing').className = `flex-1 py-2.5 transition-colors font-bold text-sm ${isExisting ? 'bg-primary-600 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300'}`;
    document.getElementById('btn-walkin').className   = `flex-1 py-2.5 transition-colors font-bold text-sm ${!isExisting ? 'bg-primary-600 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300'}`;
}

async function submitQuickSale() {
    const btn           = document.getElementById('sale-submit-btn');
    const merchandiseId = document.getElementById('sale-merchandise-id').value;
    const quantity      = document.getElementById('sale-quantity').value;
    const isWalkIn      = !document.getElementById('walkin-customer-form').classList.contains('hidden');

    try {
        if (isWalkIn) {
            const fullname = document.getElementById('walkin-fullname').value.trim();
            const phone    = document.getElementById('walkin-phone').value.trim();
            const email    = document.getElementById('walkin-email').value.trim() || null;

            if (!fullname || !phone) {
                OperationsUI.toast('Full name and phone are required for walk-in.', 'warning');
                return;
            }

            await OperationsAPI.storeWalkInOrder(merchandiseId, quantity, fullname, phone, email);
        } else {
            const customerId = document.getElementById('sale-customer-select').value;
            if (!customerId) {
                OperationsUI.toast('Please select a customer.', 'warning');
                return;
            }
            await OperationsAPI.placeOrder(customerId, merchandiseId, quantity);
        }

        OperationsUI.toast('Sale recorded successfully!', 'success');
        OperationsUI.closeModal();
        renderStore();
        updateGlobalStats();

    } catch (err) {
        console.error('Quick sale failed:', err);
        OperationsUI.toast(err.message, 'error');
    } finally {
        if (btn) {
            btn.disabled = false;
            btn.textContent = 'Confirm Purchase';
        }
    }
}

function initFinanceTab() {
    const dateInput = document.getElementById('balance-date');
    if (!dateInput) return;

    dateInput.addEventListener('change', (e) => renderBalance(e.target.value));
    renderBalance(dateInput.value);

    document.getElementById('expense-form')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        const btn = e.target.querySelector('button[type="submit"]');

        try {
            await OperationsAPI.recordExpense(Object.fromEntries(formData));
            OperationsUI.toast('Expense recorded!', 'success');
            e.target.reset();
            renderBalance(dateInput.value);
        } catch (err) {
            OperationsUI.toast(err.message, 'error');
        } finally {
            if (btn) {
                btn.disabled    = false;
                btn.textContent = 'Save Expense';
            }
        }
    });
}

async function renderBalance(date = '') {
    OperationsUI.renderBalanceShimmer();

    try {
        const result = await OperationsAPI.getDailyBalance(date);
        OperationsUI.renderBalance(result.data);
    } catch (e) {
        console.error('Failed to load balance:', e);
        const container = document.getElementById('balance-container');
        if (container) {
            container.innerHTML = `
                <div class="glass-card rounded-2xl p-8 text-center space-y-3">
                    <p class="text-rose-500 font-bold">Failed to load financial data</p>
                    <p class="text-sm text-slate-400">${e.message}</p>
                    <button onclick="renderBalance(document.getElementById('balance-date').value)"
                        class="text-xs text-primary-600 underline">Try again</button>
                </div>`;
        }
        OperationsUI.toast('Failed to load balance', 'error');
    }
}
