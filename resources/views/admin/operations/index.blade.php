<!-- resources\views\admin\operations\index.blade.php -->
@extends('layouts.operations')

@section('content')
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        @include('admin.operations.partials.sidebar')
        @include('admin.operations.partials.quick-stats-widget')
        <div class="lg:col-span-9 space-y-6">
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
@endsection