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
                ${packages.map(p => `
                    <button onclick="window.handlePackageAssign(${userId}, ${p.id})"
                        class="flex flex-col p-6 rounded-2xl border-2 border-slate-100 dark:border-slate-800 hover:border-primary-50 transition-all text-left group btn-single-action">
                        <span class="text-lg font-bold group-hover:text-primary-600 transition-colors">${p.name}</span>
                        <span class="text-sm text-slate-500">
                            ${p.total_credits} Sessions &bull; 
                            ${p.validity_days ? `${p.validity_days} Days` : 'No expiry'}
                        </span>
                        <span class="mt-4 text-2xl font-black text-slate-900 dark:text-white">
                            ${p.price != null ? OperationsUI.formatCurrency(p.price) : '—'}
                        </span>
                    </button>`).join('')}
            </div>`;

        OperationsUI.openModal('Assign New Package', content);
    } catch (e) {
        console.error('Failed to load packages:', e);
        OperationsUI.toast('Failed to load packages', 'error');
    }
}

export async function handlePackageAssign(userId, packageId) {
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
