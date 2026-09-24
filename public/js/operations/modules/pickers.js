const PICKER_SELECTOR = '[data-operations-picker]';

function isBlank(value) {
    return value === null || value === undefined || String(value).trim() === '';
}

export function parseTime24(value) {
    if (isBlank(value)) return null;

    const match = String(value)
        .trim()
        .match(/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/);

    if (!match) return null;

    const hour = Number.parseInt(match[1], 10);
    const minute = Number.parseInt(match[2], 10);
    const second = Number.parseInt(match[3] ?? '0', 10);

    if (hour > 23 || minute > 59 || second > 59) return null;

    return { hour, minute, second };
}

export function normalizeTime24(value) {
    const parsed = parseTime24(value);
    if (!parsed) return null;

    return [parsed.hour, parsed.minute, parsed.second]
        .map((part) => String(part).padStart(2, '0'))
        .join(':');
}

export function formatTime12(value) {
    const parsed = parseTime24(value);
    if (!parsed) return '';

    const period = parsed.hour >= 12 ? 'PM' : 'AM';
    const hour = parsed.hour % 12 || 12;

    return `${hour}:${String(parsed.minute).padStart(2, '0')} ${period}`;
}

export function localDateValue(date = new Date()) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
}

function currentLocale() {
    const language = document.documentElement.lang.toLowerCase();

    if (language.startsWith('ar') && window.flatpickr?.l10ns?.ar) {
        return window.flatpickr.l10ns.ar;
    }

    return window.flatpickr?.l10ns?.default ?? 'default';
}

function localizedDateFormat() {
    return document.documentElement.lang.toLowerCase().startsWith('ar')
        ? 'j F Y'
        : 'F j, Y';
}

function pickerOptions(type) {
    const common = {
        allowInput: true,
        altInput: true,
        disableMobile: true,
        locale: currentLocale(),
        onReady(_dates, _value, instance) {
            const original = instance.input;
            const display = instance.altInput;
            if (!display) return;

            const describedBy = original.getAttribute('aria-describedby');
            const label = original.id
                ? document.querySelector(`label[for="${original.id}"]`)
                : null;

            if (original.id) {
                display.id = `${original.id}-display`;
            }

            if (describedBy) {
                display.setAttribute('aria-describedby', describedBy);
            }

            if (label && display.id) {
                label.htmlFor = display.id;
            }
        },
    };

    if (type === 'time') {
        return {
            ...common,
            enableTime: true,
            enableSeconds: false,
            noCalendar: true,
            time_24hr: false,
            dateFormat: 'H:i:S',
            altFormat: 'h:i K',
        };
    }

    if (type === 'datetime') {
        return {
            ...common,
            enableTime: true,
            enableSeconds: false,
            time_24hr: false,
            dateFormat: 'Y-m-d H:i:S',
            altFormat: `${localizedDateFormat()} h:i K`,
        };
    }

    return {
        ...common,
        enableTime: false,
        dateFormat: 'Y-m-d',
        altFormat: localizedDateFormat(),
    };
}

function applyNativeFallback(input, type) {
    input.type = type === 'datetime' ? 'datetime-local' : type;

    if (type === 'time') {
        const normalized = normalizeTime24(input.value);
        input.value = normalized?.slice(0, 5) ?? '';
    }

    if (type === 'datetime' && input.value.includes(' ')) {
        input.value = input.value.replace(' ', 'T').slice(0, 16);
    }
}

export function initializeOperationsPicker(input) {
    if (!(input instanceof HTMLInputElement) || input.dataset.pickerInitialized === 'true') {
        return null;
    }

    const type = input.dataset.operationsPicker;
    if (!['date', 'time', 'datetime'].includes(type)) return null;

    input.dataset.pickerInitialized = 'true';

    if (typeof window.flatpickr !== 'function') {
        applyNativeFallback(input, type);
        return null;
    }

    const options = pickerOptions(type);
    const minDate = input.dataset.minDate || input.min;
    const maxDate = input.dataset.maxDate || input.max;

    if (minDate) options.minDate = minDate;
    if (maxDate) options.maxDate = maxDate;

    return window.flatpickr(input, options);
}

export function initializeOperationsPickers(root = document) {
    const inputs = [];

    if (root instanceof HTMLInputElement && root.matches(PICKER_SELECTOR)) {
        inputs.push(root);
    }

    if (typeof root.querySelectorAll === 'function') {
        inputs.push(...root.querySelectorAll(PICKER_SELECTOR));
    }

    inputs.forEach(initializeOperationsPicker);
}

let observer = null;

export function observeOperationsPickers() {
    initializeOperationsPickers(document);

    if (observer !== null) return;

    observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            mutation.addedNodes.forEach((node) => {
                if (node instanceof Element) {
                    initializeOperationsPickers(node);
                }
            });
        });
    });

    observer.observe(document.body, { childList: true, subtree: true });
}
