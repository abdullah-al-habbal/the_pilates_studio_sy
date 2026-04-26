@extends('layouts.pilates')

@section('title', $page->title . ' - ' . config('app.name'))

@section('content')
    <div class="max-w-4xl mx-auto px-4 py-12 sm:px-6 lg:py-16">
        @if($page->image_url)
            <div class="w-full h-64 md:h-[450px] rounded-2xl overflow-hidden mb-12 shadow-xl ring-1 ring-gray-200">
                <img src="{{ $page->image_url }}" alt="{{ $page->title }}" class="w-full h-full object-cover">
            </div>
        @endif

        <div class="bg-white rounded-3xl p-8 md:p-12 shadow-sm border border-gray-100">
            <h1 class="text-4xl md:text-5xl font-serif text-gray-900 mb-8 leading-tight font-bold">
                {{ $page->title }}
            </h1>

            <div class="prose prose-lg prose-emerald text-gray-700 leading-relaxed max-w-none">
                {!! $page->content !!}
            </div>
        </div>
    </div>

    <style>
        .prose h2 {
            font-family: 'Playfair Display', serif;
            color: #065f46;
            margin-top: 2em;
        }

        .prose p {
            margin-bottom: 1.5em;
        }

        .prose ul {
            list-style-type: disc;
            padding-inline-start: 1.5em;
            margin-bottom: 1.5em;
        }

        .prose li {
            margin-bottom: 0.5em;
        }
    </style>
@endsection