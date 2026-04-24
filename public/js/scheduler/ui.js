// public/js/scheduler/ui.js
(function(S) {
    const $ = id => document.getElementById(id);

    S.ui = {
        $: $,
        show: id => {
            const el = $(id);
            if (el) el.classList.remove('hidden');
        },
        hide: id => {
            const el = $(id);
            if (el) el.classList.add('hidden');
        },
        text: (id, val) => {
            const el = $(id);
            if (el) el.textContent = val ?? '';
        },
        html: (id, val) => {
            const el = $(id);
            if (el) el.innerHTML = val ?? '';
        },
        cls: (id, className, action) => {
            const el = $(id);
            if (!el) return;
            if (action === 'add') el.classList.add(className);
            else if (action === 'remove') el.classList.remove(className);
            else if (action === 'toggle') el.classList.toggle(className);
        },
        val: (id, value) => {
            const el = $(id);
            if (el) {
                if (value !== undefined) el.value = value;
                return el.value;
            }
            return null;
        }
    };
})(window.Scheduler);
