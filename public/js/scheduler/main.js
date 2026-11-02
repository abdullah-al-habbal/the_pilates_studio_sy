// public/js/scheduler/main.js
document.addEventListener('DOMContentLoaded', () => {
    const { state, ui, events } = window.Scheduler;
    ui.val('input-date', state.selectedDate);
    events.bind();
    events.loadInstructors();
    events.loadCategories();
    events.initFlatpickr();
    events.loadSessions();
});
