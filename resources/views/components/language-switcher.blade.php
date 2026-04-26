<div x-data="{ open: false }" class="relative">
    <button @click="open = !open" class="flex items-center gap-1 text-gray-600 hover:text-emerald-600 transition">
        <span>{{ strtoupper($currentLocale) }}</span>
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
        </svg>
    </button>
    <div x-show="open" @click.away="open = false"
        class="absolute right-0 mt-2 w-24 bg-white rounded shadow-lg z-50 py-1 text-sm" style="display: none;">
        @foreach($languages as $lang)
            <a href="{{ route('locale.switch', $lang->code) }}"
                class="block px-4 py-2 hover:bg-emerald-50 {{ $lang->code === $currentLocale ? 'text-emerald-600 font-semibold' : 'text-gray-700' }}">
                {{ strtoupper($lang->code) }}
            </a>
        @endforeach
    </div>
</div>