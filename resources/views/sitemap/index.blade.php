<?= '<?xml version="1.0" encoding="UTF-8"?>' ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:xhtml="http://www.w3.org/1999/xhtml">
    {{-- Homepage --}}
    <url>
        <loc>{{ url('/') }}</loc>
        <lastmod>{{ $lastModified }}</lastmod>
        <changefreq>weekly</changefreq>
        <priority>1.0</priority>
        <xhtml:link rel="alternate" hreflang="en" href="{{ url('/') }}" />
        <xhtml:link rel="alternate" hreflang="ar" href="{{ url('/ar') }}" />
    </url>
    {{-- Static Pages --}}
    @foreach(\App\Models\StaticPage::where('is_active', true)->get() as $page)
    <url>
        <loc>{{ url("/web/static-pages/{$page->slug}") }}</loc>
        <lastmod>{{ $page->updated_at->toIso8601String() }}</lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.8</priority>
    </url>
    @endforeach
</urlset>
