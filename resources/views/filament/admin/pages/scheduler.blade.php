<!-- filePath: resources\views\filament\admin\pages\scheduler.blade.php -->
<x-filament-panels::page>
    @if($sessions->isEmpty())
        <x-filament::empty-state
            icon="heroicon-o-calendar-days"
            :heading="__('dashboard.pages.scheduler.empty.title')"
            :description="__('dashboard.pages.scheduler.empty.description')"
        />
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
            @foreach($sessions as $session)
                <x-scheduler.session-card :session="$session" />

                <x-filament::modal :id="'attendance-' . $session->id" width="2xl">
                    <x-slot name="heading">
                        {{ __('dashboard.pages.scheduler.modal.heading', ['class' => $session->class?->title[app()->getLocale()] ?? $session->class?->title['en'] ?? '—', 'date' => $session->date->format('M j')]) }}
                    </x-slot>

                    <x-scheduler.attendance-modal :session="$session" :is-full="$session->total_spots > 0 && $session->bookingSessions->count() >= $session->total_spots" />

                    <x-slot name="footerActions">
                        <x-filament::button color="gray" x-on:click="$dispatch('close-modal', { id: 'attendance-{{ $session->id }}' })">
                            {{ __('dashboard.pages.scheduler.modal.close') }}
                        </x-filament::button>
                    </x-slot>
                </x-filament::modal>
            @endforeach
        </div>
    @endif
</x-filament-panels::page>