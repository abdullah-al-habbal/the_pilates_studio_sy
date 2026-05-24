@extends('layouts.operations')

@php
    use App\Services\Currency\CurrencyService;
    $currencyService = app(
    CurrencyService::class);
    $activeCurrencies = $currencyService->getAllActiveCurrencies();
@endphp

@section('content')
    <script>
        window.OperationsCurrencies = @json($activeCurrencies);
    </script>
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        @include('admin.operations.partials.sidebar')

        <div class="lg:col-span-9 space-y-6">

            @include('admin.operations.partials.quick-stats-widget')

            <div id="tab-content-container" class="transition-all duration-300">
                @include('admin.operations.partials.main-content-loader')
            </div>

        </div>
    </div>

    <template id="tpl-clients">
        @include('admin.operations.partials.tab-clients')
    </template>
    <template id="tpl-store">
        @include('admin.operations.partials.tab-store')
    </template>
    <template id="tpl-finance">
        @include('admin.operations.partials.tab-finance')
    </template>
    <template id="tpl-notifications">
        @include('admin.operations.partials.tab-notifications')
    </template>
@endsection