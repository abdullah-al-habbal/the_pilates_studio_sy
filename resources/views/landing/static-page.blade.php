@extends('layouts.landing')

@section('content')
    <section class="pt-32 pb-24 px-4 max-w-4xl mx-auto">
        <h1 class="text-3xl sm:text-4xl font-bold text-slate-900 dark:text-white mb-8">{{ $page->getTranslation('title', app()->getLocale()) }}</h1>
        <div class="prose dark:prose-invert max-w-none">
            {!! $page->getTranslation('content', app()->getLocale()) !!}
        </div>
    </section>
@endsection
