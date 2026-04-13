<x-filament-panels::page>

    <div class="space-y-4">
        <x-filament::tabs>

            <x-filament::tabs.item
                :active="$this->period === 'daily'"
                wire:click="$set('period', 'daily')"
                icon="heroicon-o-sun">
                {{ __('dashboard.pages.reports.filters.daily', [], 'en') ?? 'Daily' }}
            </x-filament::tabs.item>

            <x-filament::tabs.item
                :active="$this->period === 'monthly'"
                wire:click="$set('period', 'monthly')"
                icon="heroicon-o-calendar">
                {{ __('dashboard.pages.reports.filters.monthly', [], 'en') ?? 'Monthly' }}
            </x-filament::tabs.item>

            <x-filament::tabs.item
                :active="$this->period === 'yearly'"
                wire:click="$set('period', 'yearly')"
                icon="heroicon-o-chart-bar">
                {{ __('dashboard.pages.reports.filters.yearly', [], 'en') ?? 'Yearly' }}
            </x-filament::tabs.item>

            <x-filament::tabs.item
                :active="$this->period === 'custom'"
                wire:click="$set('period', 'custom')"
                icon="heroicon-o-clock">
                {{ __('dashboard.pages.reports.filters.custom', [], 'en') ?? 'Custom' }}
            </x-filament::tabs.item>

        </x-filament::tabs>

        @if($this->period === 'daily')
            <x-filament::section compact>
                <div class="flex items-center gap-3">
                    <x-heroicon-o-calendar-days style="width:1rem;height:1rem;" class="text-gray-400 shrink-0" />
                    <label class="text-sm font-medium text-gray-600 dark:text-gray-400 shrink-0">
                        {{ __('dashboard.pages.reports.filters.select_date', [], 'en') ?? 'Date' }}
                    </label>
                    <input
                        type="date"
                        wire:model.live="dailyDate"
                        max="{{ now()->toDateString() }}"
                        class="rounded-lg border border-gray-300 dark:border-gray-600
                               bg-white dark:bg-gray-800 text-sm text-gray-900 dark:text-gray-100
                               px-3 py-1.5 shadow-sm focus:border-primary-500 focus:ring-primary-500" />
                </div>
            </x-filament::section>
        @endif

        @if($this->period === 'monthly')
            <x-filament::section compact>
                <div class="flex items-center gap-3">
                    <x-heroicon-o-calendar style="width:1rem;height:1rem;" class="text-gray-400 shrink-0" />
                    <label class="text-sm font-medium text-gray-600 dark:text-gray-400 shrink-0">
                        {{ __('dashboard.pages.reports.filters.select_month', [], 'en') ?? 'Month' }}
                    </label>
                    <input
                        type="month"
                        wire:model.live="month"
                        max="{{ now()->format('Y-m') }}"
                        class="rounded-lg border border-gray-300 dark:border-gray-600
                               bg-white dark:bg-gray-800 text-sm text-gray-900 dark:text-gray-100
                               px-3 py-1.5 shadow-sm focus:border-primary-500 focus:ring-primary-500" />
                </div>
            </x-filament::section>
        @endif

        @if($this->period === 'yearly')
            <x-filament::section compact>
                <div class="flex items-center gap-3">
                    <x-heroicon-o-chart-bar style="width:1rem;height:1rem;" class="text-gray-400 shrink-0" />
                    <label class="text-sm font-medium text-gray-600 dark:text-gray-400 shrink-0">
                        {{ __('dashboard.pages.reports.filters.select_year', [], 'en') ?? 'Year' }}
                    </label>
                    <select
                        wire:model.live="year"
                        class="rounded-lg border border-gray-300 dark:border-gray-600
                               bg-white dark:bg-gray-800 text-sm text-gray-900 dark:text-gray-100
                               px-3 py-1.5 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        @foreach(range(now()->year, now()->year - 5) as $y)
                            <option value="{{ $y }}">{{ $y }}</option>
                        @endforeach
                    </select>
                </div>
            </x-filament::section>
        @endif

        @if($this->period === 'custom')
            <x-filament::section compact>
                <div class="flex flex-wrap items-center gap-6">
                    <div class="flex items-center gap-3">
                        <x-heroicon-o-clock style="width:1rem;height:1rem;" class="text-gray-400 shrink-0" />
                        <label class="text-sm font-medium text-gray-600 dark:text-gray-400 shrink-0">
                            {{ __('dashboard.pages.reports.filters.start_date', [], 'en') ?? 'From' }}
                        </label>
                        <input
                            type="date"
                            wire:model.live="customStart"
                            max="{{ $this->customEnd ?: now()->toDateString() }}"
                            class="rounded-lg border border-gray-300 dark:border-gray-600
                                   bg-white dark:bg-gray-800 text-sm text-gray-900 dark:text-gray-100
                                   px-3 py-1.5 shadow-sm focus:border-primary-500 focus:ring-primary-500" />
                    </div>
                    <div class="flex items-center gap-3">
                        <x-heroicon-o-arrow-right style="width:1rem;height:1rem;" class="text-gray-400 shrink-0" />
                        <label class="text-sm font-medium text-gray-600 dark:text-gray-400 shrink-0">
                            {{ __('dashboard.pages.reports.filters.end_date', [], 'en') ?? 'To' }}
                        </label>
                        <input
                            type="date"
                            wire:model.live="customEnd"
                            min="{{ $this->customStart ?: '' }}"
                            max="{{ now()->toDateString() }}"
                            class="rounded-lg border border-gray-300 dark:border-gray-600
                                   bg-white dark:bg-gray-800 text-sm text-gray-900 dark:text-gray-100
                                   px-3 py-1.5 shadow-sm focus:border-primary-500 focus:ring-primary-500" />
                    </div>
                </div>
            </x-filament::section>
        @endif
    </div>

    @if($this->period === 'custom' && (!$this->customStart || !$this->customEnd))

        <x-filament::section>
            <div class="flex flex-col items-center justify-center py-12 gap-4
                        text-gray-400 dark:text-gray-600">
                <x-heroicon-o-clock style="width:3rem;height:3rem;" />
                <div class="text-center">
                    <p class="text-base font-medium">
                        {{ __('dashboard.pages.reports.filters.custom_hint', [], 'en') ?? 'Select a date range to generate the report.' }}
                    </p>
                    <p class="text-sm mt-1">
                        {{ __('Pick a start and end date using the controls above.', [], 'en') }}
                    </p>
                </div>
            </div>
        </x-filament::section>

    @else

        {{ $this->reportsInfolist }}

    @endif

</x-filament-panels::page>
