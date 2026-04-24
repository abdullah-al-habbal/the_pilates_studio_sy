{{-- resources/views/admin/scheduler/partials/main/session-list.blade.php --}}
<div x-show="!loading && !error && sessions.length > 0" class="grid gap-3">
    <template x-for="session in sessions" :key="session.id">
        @include('admin.scheduler.partials.main.session-card')
    </template>
</div>