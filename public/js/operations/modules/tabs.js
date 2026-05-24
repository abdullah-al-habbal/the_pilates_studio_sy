// filePath: /home/lenovo/work/projects/the_pilates_studio_sy/public/js/operations/modules/tabs.js
import { initClientsTab } from './clients.js';
import { initStoreTab } from './store.js';
import { initFinanceTab } from './finance.js';
import { initNotificationsTab } from './notifications.js';

export function initTabs() {
    const buttons = document.querySelectorAll('[data-tab]');
    
    const switchTabFromHash = () => {
        const hash = window.location.hash.replace('#', '');
        const tab = ['clients', 'store', 'finance', 'notifications'].includes(hash) ? hash : 'clients';
        
        loadTab(tab);

        buttons.forEach(b => {
            const isActive = b.dataset.tab === tab;
            b.classList.toggle('bg-primary-600', isActive);
            b.classList.toggle('text-white', isActive);
            b.classList.toggle('shadow-lg', isActive);
            b.classList.toggle('shadow-primary-500/20', isActive);
            b.classList.toggle('active-tab', isActive);
            b.classList.toggle('hover:bg-slate-100', !isActive);
            b.classList.toggle('dark:hover:bg-slate-800', !isActive);
        });
    };

    buttons.forEach(btn => {
        btn.addEventListener('click', () => {
            window.location.hash = btn.dataset.tab;
        });
    });

    window.addEventListener('hashchange', switchTabFromHash);
    
    switchTabFromHash();
}

export function loadTab(tab) {
    const container = document.getElementById('tab-content-container');
    const template  = document.getElementById(`tpl-${tab}`);
    if (!template) return;

    container.innerHTML = template.innerHTML;

    if (tab === 'clients') setTimeout(initClientsTab, 0);
    if (tab === 'store')   setTimeout(initStoreTab, 0);
    if (tab === 'finance') setTimeout(initFinanceTab, 0);
    if (tab === 'notifications') setTimeout(initNotificationsTab, 0);
}

export async function updateGlobalStats(date = '') {
    const container = document.getElementById('quick-stats-currency-list');
    if (!container) return;

    container.innerHTML = '<p class="text-sm text-slate-400">Loading snapshot...</p>';

    try {
        const result = await OperationsAPI.getDailyBalance(date, []);
        OperationsUI.renderDailySnapshot(result.data);
    } catch (e) {
        console.error('Failed to load global stats:', e);
        container.innerHTML = `<p class="text-rose-500 text-sm">Snapshot unavailable. <button onclick="window.updateGlobalStats()" class="underline">Retry</button></p>`;
    }
}

export function renderShimmerRows(tbodyId, colCount = 6, rowCount = 6) {
    const tbody = document.getElementById(tbodyId);
    if (!tbody) return;

    const widths = ['w-50','w-30','w-20','w-15'];
    tbody.innerHTML = Array.from({ length: rowCount }, () => `
        <tr class="shimmer-row border-b border-slate-100 dark:border-slate-800/50">
            ${Array.from({ length: colCount }, (_, i) => `
                <td><div class="shimmer-cell ${widths[i % widths.length]}"></div></td>
            `).join('')}
        </tr>`).join('');
}
