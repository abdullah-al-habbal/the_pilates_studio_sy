<!-- filePath: resources\views\filament\admin\pages\scheduler.blade.php -->
<x-filament-panels::page>
    @if($sessions->isEmpty())
        <x-filament::empty-state icon="heroicon-o-calendar-days" :heading="__('dashboard.pages.scheduler.empty.title')"
            :description="__('dashboard.pages.scheduler.empty.description')" />
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
            @foreach($sessions as $session)
                @php
                    $actions = $this->getSessionActions($session->id);
                    $attendanceAction = collect($actions)->first();
                @endphp
                <x-scheduler.session-card :session="$session" :attendance-action="$attendanceAction" />
            @endforeach
        </div>
    @endif
</x-filament-panels::page>