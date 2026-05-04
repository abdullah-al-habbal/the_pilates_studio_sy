// public/js/operations/tabs.js
function initTabs() {
    const buttons = document.querySelectorAll('[data-tab]');
    buttons.forEach(btn => {
        btn.addEventListener('click', () => {
            const tab = btn.dataset.tab;
            loadTab(tab);

            buttons.forEach(b => {
                b.classList.remove('bg-primary-600','text-white','shadow-lg','shadow-primary-500/20','active-tab');
                b.classList.add('hover:bg-slate-100','dark:hover:bg-slate-800');
            });
            btn.classList.add('bg-primary-600','text-white','shadow-lg','shadow-primary-500/20','active-tab');
            btn.classList.remove('hover:bg-slate-100','dark:hover:bg-slate-800');
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

function renderShimmerRows(tbodyId, colCount = 6, rowCount = 6) {
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