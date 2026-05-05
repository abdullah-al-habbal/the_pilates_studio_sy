import { renderClients, showClientDetails } from './clients.js';

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
        const result   = await OperationsAPI.getPackages();
        const packages = result.data;

        const content = `
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                ${packages.map(p => {
                    const price = p.prices && p.prices.length > 0 ? p.prices[0].amount : (p.price || 0);
                    const currId = p.prices && p.prices.length > 0 ? p.prices[0].currency_id : (window.OperationsCurrencies?.[0]?.id || 1);
                    return `
                    <div class="flex flex-col p-6 rounded-2xl border-2 border-slate-100 dark:border-slate-800 transition-all text-left group">
                        <span class="text-lg font-bold text-slate-900 dark:text-white">${p.name}</span>
                        <span class="text-sm text-slate-500 mb-4">
                            ${p.total_credits} Sessions &bull; 
                            ${p.validity_days ? `${p.validity_days} Days` : 'No expiry'}
                        </span>
                        
                        <div class="space-y-3 mt-auto">
                            <div class="flex gap-2">
                                <select id="currency-${p.id}" class="flex-1 px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary-500">
                                    ${(window.OperationsCurrencies || []).map(c => 
                                        `<option value="${c.id}" ${c.id == currId ? 'selected' : ''}>${c.code} (${c.symbol})</option>`
                                    ).join('')}
                                </select>
                                <input type="number" id="amount-${p.id}" value="${price}" min="0" class="flex-1 px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg text-sm outline-none focus:ring-2 focus:ring-primary-500" placeholder="Amount">
                            </div>
                            <button onclick="window.handlePackageAssign(${userId}, ${p.id})" class="w-full bg-primary-600 hover:bg-primary-700 text-white font-bold py-2 rounded-xl transition-colors btn-single-action">
                                Assign Package
                            </button>
                        </div>
                    </div>`;
                }).join('')}
            </div>`;

        OperationsUI.openModal('Assign New Package', content);
    } catch (e) {
        console.error('Failed to load packages:', e);
        OperationsUI.toast('Failed to load packages', 'error');
    }
}

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
        console.error('Failed to assign package:', e);
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

window.showPackageAssignment = showPackageAssignment;
window.handlePackageAssign = handlePackageAssign;
window.handleFreeze = handleFreeze;
window.handleUnfreeze = handleUnfreeze;
