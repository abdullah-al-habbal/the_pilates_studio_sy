{{-- filePath: resources/views/filament/components/no-image-placeholder.blade.php --}}
@props([
    'text' => 'No Image',
    'size' => 50,
    'class' => '',
])

<div
    {{ $attributes->merge(['class' => 'flex items-center justify-center bg-gray-100 dark:bg-gray-800 rounded-lg text-gray-500 dark:text-gray-400 ' . $class]) }}
    style="width: {{ $size }}px; height: {{ $size }}px;"
>
    <span class="text-xs text-center px-1">{{ $text }}</span>
</div>
