import { initTheme } from './modules/theme.js';
import { initTabs, loadTab, updateGlobalStats } from './modules/tabs.js';
import { initClientsTab } from './modules/clients.js';
import { initStoreTab } from './modules/store.js';
import { initFinanceTab } from './modules/finance.js';

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
