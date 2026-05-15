// public\js\operations\modules\packages.js
import { renderClients, showClientDetails } from './clients.js';

async function createNewPackage(userId, formData) {
    try {
        await OperationsAPI.createPackage(formData);
        OperationsUI.toast('Package created!', 'success');
        showPackageAssignment(userId);
    } catch (e) {
        OperationsUI.toast(e.message, 'error');
    }
}

async function updatePackage(userId, packageId, formData) {
    try {
        await OperationsAPI.updatePackage(packageId, formData);
        OperationsUI.toast('Package updated!', 'success');
        showPackageAssignment(userId);
    } catch (e) {
        OperationsUI.toast(e.message, 'error');
    }
}

async function deletePackage(userId, packageId) {
    if (!confirm('Delete this package? It will no longer be assignable.')) return;
    try {
        await OperationsAPI.deletePackage(packageId);
        OperationsUI.toast('Package deleted.', 'success');
        showPackageAssignment(userId);
    } catch (e) {
        OperationsUI.toast(e.message, 'error');
    }
}

export async function showPackageAssignment(userId) {
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
        const result = await OperationsAPI.getPackages();
        const packages = result.data;
        const gridHtml = packages.map(p => renderPackageCard(p, userId)).join('');

        const content = `
            <div class="mb-4">
                <button id="show-create-package-form" class="w-full bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 py-2.5 rounded-xl font-medium text-sm transition-colors">
                    + New Package
                </button>
            </div>
            <div id="package-grid" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                ${gridHtml}
            </div>
            <!-- Creation form -->
            <div id="create-package-form" class="hidden space-y-4">${renderPackageForm('create', userId, null)}</div>
            <!-- Global hidden edit container (will be populated dynamically) -->
            <div id="edit-package-container" class="hidden"></div>`;

        OperationsUI.openModal('Assign New Package', content);
        // Attach handlers after modal is rendered
        attachGlobalHandlers(userId);

        // Initial trigger to ensure correct amounts are shown for each card
        packages.forEach(p => {
            setTimeout(() => window.updatePackageAmount(p.id), 0);
        });
    } catch (e) {
        console.error('Failed to load packages:', e);
        OperationsUI.toast('Failed to load packages', 'error');
    }
}

function renderPackageCard(p, userId) {
    const prices = (p.prices || []).reduce((acc, pr) => {
        acc[pr.currency_id] = pr.amount;
        return acc;
    }, {});
    const pricesJson = JSON.stringify(prices).replace(/"/g, '&quot;');
    
    const currId = p.prices && p.prices.length > 0 ? p.prices[0].currency_id : (window.OperationsCurrencies?.[0]?.id || 1);
    const price = prices[currId] ?? 0;

    return `
        <div id="card-${p.id}" class="flex flex-col p-6 rounded-2xl border-2 border-slate-100 dark:border-slate-800 transition-all text-left group">
            <div class="flex justify-between items-start">
                <span class="text-lg font-bold text-slate-900 dark:text-white">${p.name}</span>
                <div class="flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                    <button onclick="window.editPackage(${userId}, ${p.id})" title="Edit" class="p-1 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                    </button>
                    <button onclick="window.deletePackage(${userId}, ${p.id})" title="Delete" class="p-1 hover:bg-rose-100 dark:hover:bg-rose-900/20 rounded-lg text-rose-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                </div>
            </div>
            <span class="text-sm text-slate-500 mb-4">
                ${p.total_credits} Sessions &bull; ${p.validity_days ? `${p.validity_days} Days` : 'No expiry'}
            </span>
            <div class="space-y-3 mt-auto">
                <div class="flex gap-2">
                    <select id="currency-${p.id}" onchange="window.updatePackageAmount(${p.id})"
                            class="flex-1 px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary-500">
                        ${(window.OperationsCurrencies || []).map(c => `<option value="${c.id}" ${c.id == currId ? 'selected' : ''}>${c.code} (${c.symbol})</option>`).join('')}
                    </select>
                    <input type="number" id="amount-${p.id}" value="${price}" min="0" readonly
                           class="flex-1 px-3 py-2 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-sm outline-none cursor-not-allowed"
                           placeholder="Amount"
                           data-prices="${pricesJson}">
                </div>
                <button id="assign-btn-${p.id}" onclick="window.handlePackageAssign(${userId}, ${p.id})" 
                        class="w-full bg-primary-600 hover:bg-primary-700 text-white font-bold py-2 rounded-xl transition-colors btn-single-action">
                    Assign Package
                </button>
            </div>
        </div>`;
}

function renderPackageForm(context, userId, packageData = null) {
    const isEdit = context === 'edit';
    const defaultValidity = packageData?.validity_days ?? '';
    const defaultCredits  = packageData?.total_credits ?? 10;
    const defaultName     = packageData?.name ?? '';
    const defaultCurrId   = packageData?.prices?.[0]?.currency_id ?? (window.OperationsCurrencies?.[0]?.id || 1);
    const defaultAmount   = packageData?.prices?.[0]?.amount ?? 0;

    return `
        <div class="space-y-4 p-6 glass-card rounded-2xl border-2 border-primary-500/20">
            <div>
                <label class="text-xs font-bold text-slate-500 uppercase">Package Name</label>
                <input id="${context}-pkg-name" value="${defaultName}" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary-500" placeholder="e.g. Premium 10 Sessions">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="text-xs font-bold text-slate-500 uppercase">Sessions</label>
                    <input type="number" id="${context}-pkg-credits" min="1" value="${defaultCredits}" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary-500">
                </div>
                <div>
                    <label class="text-xs font-bold text-slate-500 uppercase">Validity (days, optional)</label>
                    <input type="number" id="${context}-pkg-validity" min="0" value="${defaultValidity}" placeholder="Leave empty for no expiry" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary-500">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="text-xs font-bold text-slate-500 uppercase">Currency</label>
                    <select id="${context}-pkg-currency" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary-500">
                        ${(window.OperationsCurrencies || []).map(c => `<option value="${c.id}" ${c.id == defaultCurrId ? 'selected' : ''}>${c.code} (${c.symbol})</option>`).join('')}
                    </select>
                </div>
                <div>
                    <label class="text-xs font-bold text-slate-500 uppercase">Price</label>
                    <input type="number" id="${context}-pkg-price" min="0" value="${defaultAmount}" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary-500">
                </div>
            </div>
            <div class="flex gap-2 pt-2">
                <button id="cancel-${context}-package" class="flex-1 bg-slate-100 dark:bg-slate-800 py-2.5 rounded-xl text-sm font-medium btn-single-action transition-colors">Cancel</button>
                <button id="submit-${context}-package" class="flex-1 bg-primary-600 hover:bg-primary-700 text-white py-2.5 rounded-xl text-sm font-bold btn-single-action transition-all">
                    ${isEdit ? 'Save Changes' : 'Create & Reload'}
                </button>
            </div>
        </div>`;
}

function attachGlobalHandlers(userId) {
    document.getElementById('show-create-package-form')?.addEventListener('click', () => {
        document.getElementById('package-grid').classList.add('hidden');
        document.getElementById('edit-package-container').classList.add('hidden');
        document.getElementById('create-package-form').classList.remove('hidden');
        document.getElementById('show-create-package-form').classList.add('hidden');
    });

    document.getElementById('cancel-create-package')?.addEventListener('click', () => {
        document.getElementById('create-package-form').classList.add('hidden');
        document.getElementById('package-grid').classList.remove('hidden');
        document.getElementById('show-create-package-form').classList.remove('hidden');
    });

    document.getElementById('submit-create-package')?.addEventListener('click', () => {
        const data = {
            name:          document.getElementById('create-pkg-name')?.value,
            total_credits: document.getElementById('create-pkg-credits')?.value,
            validity_days: document.getElementById('create-pkg-validity')?.value,
            currency_id:   document.getElementById('create-pkg-currency')?.value,
            amount:        document.getElementById('create-pkg-price')?.value,
        };

        if (!data.name || !data.total_credits) {
            OperationsUI.toast('Name and sessions are required.', 'error');
            return;
        }

        createNewPackage(userId, data);
    });
}

window.editPackage = async function(userId, packageId) {
    try {
        const result = await OperationsAPI.getPackages();
        const packages = result.data;
        const pkg = packages.find(p => p.id == packageId);
        if (!pkg) throw new Error('Package not found.');

        const card = document.getElementById(`card-${packageId}`);
        if (!card) return;

        // Hide the grid and other forms
        document.getElementById('package-grid').classList.add('hidden');
        document.getElementById('show-create-package-form').classList.add('hidden');
        document.getElementById('create-package-form').classList.add('hidden');

        const container = document.getElementById('edit-package-container');
        container.innerHTML = renderPackageForm('edit', userId, pkg);
        container.classList.remove('hidden');

        // Cancel edit
        document.getElementById('cancel-edit-package')?.addEventListener('click', () => {
            container.classList.add('hidden');
            document.getElementById('package-grid').classList.remove('hidden');
            document.getElementById('show-create-package-form').classList.remove('hidden');
        });

        // Submit edit
        document.getElementById('submit-edit-package')?.addEventListener('click', () => {
            const data = {
                name:          document.getElementById('edit-pkg-name')?.value,
                total_credits: document.getElementById('edit-pkg-credits')?.value,
                validity_days: document.getElementById('edit-pkg-validity')?.value,
                currency_id:   document.getElementById('edit-pkg-currency')?.value,
                amount:        document.getElementById('edit-pkg-price')?.value,
            };

            if (!data.name || !data.total_credits) {
                OperationsUI.toast('Name and sessions are required.', 'error');
                return;
            }

            updatePackage(userId, packageId, data);
        });
    } catch (e) {
        OperationsUI.toast('Could not load package for editing.', 'error');
    }
};

window.deletePackage = function(userId, packageId) {
    deletePackage(userId, packageId);
};

export async function handlePackageAssign(userId, packageId) {
    const currencyId = document.getElementById(`currency-${packageId}`)?.value;
    const paidAmount = document.getElementById(`amount-${packageId}`)?.value;

    if (!currencyId || !paidAmount) {
        OperationsUI.toast('Please select a currency and enter a valid amount.', 'error');
        return;
    }

    try {
        await OperationsAPI.assignPackage(userId, packageId, currencyId, paidAmount);
        OperationsUI.toast('Package assigned successfully!', 'success');
        OperationsUI.closeModal();
        renderClients();
    } catch (e) {
        OperationsUI.toast(e.message, 'error');
    }
}

export async function handleFreeze(bookingId, userId) {
    if (!confirm('Freeze this package? Validity calculations will pause until unfrozen.')) return;
    try {
        await OperationsAPI.freezeBooking(bookingId);
        OperationsUI.toast('Package frozen successfully.', 'success');
        showClientDetails(userId);
    } catch (e) {
        OperationsUI.toast(e.message, 'error');
    }
}

export async function handleUnfreeze(bookingId, userId) {
    if (!confirm('Unfreeze package? A new replacement booking will be created for the remaining validity.')) return;
    try {
        await OperationsAPI.unfreezeBooking(bookingId);
        OperationsUI.toast('Package unfrozen and resumed.', 'success');
        showClientDetails(userId);
    } catch (e) {
        OperationsUI.toast(e.message, 'error');
    }
}

window.updatePackageAmount = function(packageId) {
    const currencySelect = document.getElementById(`currency-${packageId}`);
    const amountInput = document.getElementById(`amount-${packageId}`);
    if (!currencySelect || !amountInput) return;

    const pricesJson = amountInput.getAttribute('data-prices');
    if (!pricesJson) return;

    const prices = JSON.parse(pricesJson.replace(/&quot;/g, '"'));
    const selectedCurrencyId = parseInt(currencySelect.value, 10);
    const amount = prices[selectedCurrencyId] ?? 0;

    amountInput.value = amount;

    // Optionally show a warning if no price exists for this currency
    const assignBtn = document.getElementById(`assign-btn-${packageId}`);
    if (assignBtn) {
        if (amount === 0 && !prices.hasOwnProperty(selectedCurrencyId)) {
            assignBtn.disabled = true;
            assignBtn.title = 'No price available for this currency';
            assignBtn.classList.add('opacity-50', 'cursor-not-allowed');
        } else {
            assignBtn.disabled = false;
            assignBtn.removeAttribute('title');
            assignBtn.classList.remove('opacity-50', 'cursor-not-allowed');
        }
    }
};

window.showPackageAssignment = showPackageAssignment;
window.handlePackageAssign = handlePackageAssign;
window.handleFreeze = handleFreeze;
window.handleUnfreeze = handleUnfreeze;
window.updatePackageAmount = window.updatePackageAmount;
