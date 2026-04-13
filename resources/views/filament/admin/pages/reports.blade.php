<x-filament-panels::page>
    <div class="space-y-8">
        {{-- Stats Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div
                class="bg-white dark:bg-gray-900 overflow-hidden shadow rounded-lg border border-gray-200 dark:border-gray-800">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="shrink-0 bg-primary-500 rounded-md p-3">
                            <x-heroicon-o-currency-dollar class="h-6 w-6 text-white" />
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">
                                    {{ __('dashboard.pages.reports.stats.total_revenue') }}
                                </dt>
                                <dd class="flex items-baseline">
                                    <div class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                                        {{ number_format($stats['total_revenue']) }} SYP
                                    </div>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div
                class="bg-white dark:bg-gray-900 overflow-hidden shadow rounded-lg border border-gray-200 dark:border-gray-800">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="shrink-0 bg-success-500 rounded-md p-3">
                            <x-heroicon-o-ticket class="h-6 w-6 text-white" />
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">
                                    {{ __('dashboard.pages.reports.stats.booking_revenue') }}
                                </dt>
                                <dd class="flex items-baseline">
                                    <div class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                                        {{ number_format($stats['booking_revenue']) }} SYP
                                    </div>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div
                class="bg-white dark:bg-gray-900 overflow-hidden shadow rounded-lg border border-gray-200 dark:border-gray-800">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="shrink-0 bg-amber-500 rounded-md p-3">
                            <x-heroicon-o-shopping-bag class="h-6 w-6 text-white" />
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">
                                    {{ __('dashboard.pages.reports.stats.store_revenue') }}
                                </dt>
                                <dd class="flex items-baseline">
                                    <div class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                                        {{ number_format($stats['merchandise_revenue']) }} SYP
                                    </div>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            {{-- Popular Classes --}}
            <div class="bg-white dark:bg-gray-900 shadow rounded-lg border border-gray-200 dark:border-gray-800">
                <div class="p-6 border-b border-gray-200 dark:border-gray-800">
                    <h3 class="text-lg font-bold flex items-center gap-2">
                        <x-heroicon-o-fire class="w-5 h-5 text-amber-500" />
                        {{ __('dashboard.pages.reports.popular_classes.heading') }}
                    </h3>
                </div>
                <div class="p-6">
                    <div class="space-y-6">
                        @foreach($popularClasses as $class)
                            @php
                                $classTitle = $class->title[app()->getLocale()] ?? $class->title['en'] ?? '';
                            @endphp
                            <div class="space-y-2">
                                <div class="flex items-center justify-between">
                                    <span class="font-medium text-gray-700 dark:text-gray-300">{{ $classTitle }}</span>
                                    <span class="text-sm text-gray-500">
                                        {{ __('dashboard.pages.reports.popular_classes.attendees', ['count' => $class->total_attendance]) }}
                                    </span>
                                </div>
                                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 text-right">
                                    <div class="bg-primary-600 h-2 rounded-full"
                                        style="width: {{ min(100, ($class->total_attendance / max(1, $stats['total_bookings'])) * 500) }}%">
                                    </div>
                                </div>
                                <div class="flex justify-between text-xs text-gray-400">
                                    <span>{{ __('dashboard.pages.reports.popular_classes.sessions', ['count' => $class->sessions_count]) }}</span>
                                    <span>{{ __('dashboard.pages.reports.popular_classes.avg', ['count' => $class->avg_attendance]) }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Top Merchandise --}}
            <div class="bg-white dark:bg-gray-900 shadow rounded-lg border border-gray-200 dark:border-gray-800">
                <div class="p-6 border-b border-gray-200 dark:border-gray-800">
                    <h3 class="text-lg font-bold flex items-center gap-2">
                        <x-heroicon-o-chart-bar-square class="w-5 h-5 text-success-500" />
                        {{ __('dashboard.pages.reports.top_merchandise.heading') }}
                    </h3>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        @foreach($merchandiseSales as $item)
                            @php
                                $itemName = $item->name[app()->getLocale()] ?? $item->name['en'] ?? '';
                            @endphp
                            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-white dark:bg-gray-700 rounded-md shadow-sm shrink-0">
                                        <x-heroicon-o-cube class="w-5 h-5 text-gray-400" />
                                    </div>
                                    <span class="font-medium text-gray-900 dark:text-gray-100">{{ $itemName }}</span>
                                </div>
                                <div class="text-right">
                                    <p class="font-bold text-gray-900 dark:text-gray-100">
                                        {{ number_format($item->revenue) }} SYP</p>
                                    <p class="text-xs text-gray-500">
                                        {{ __('dashboard.pages.reports.top_merchandise.sold', ['count' => $item->quantity]) }}
                                    </p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>