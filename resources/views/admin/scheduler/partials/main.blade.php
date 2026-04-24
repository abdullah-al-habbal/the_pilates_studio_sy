<!-- resources/views/admin/scheduler/partial/main.blade.php -->
<main class="flex-1 max-w-5xl w-full mx-auto px-4 sm:px-6 py-6 space-y-4">
    @include('admin.scheduler.partials.main.error-state')
    @include('admin.scheduler.partials.main.empty-state')
    @include('admin.scheduler.partials.main.skeleton-loader')
    @include('admin.scheduler.partials.main.session-list')
    @include('admin.scheduler.partials.main.pagination')
</main>