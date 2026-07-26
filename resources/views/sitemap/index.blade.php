@php
    use App\Models\StaticPage;
    $staticPages = StaticPage::query()
        ->where('is_active', true)
        ->get();
@endphp

<?= '<?xml version="1.0" encoding="UTF-8"?>' ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:xhtml="http://www.w3.org/1999/xhtml">
    <url>
        <loc>{{ url('/') }}</loc>
        <lastmod>{{ $lastModified }}</lastmod>
        <changefreq>weekly</changefreq>
        <priority>1.0</priority>
        <xhtml:link rel="alternate" hreflang="en" href="{{ url('/') }}" />
        <xhtml:link rel="alternate" hreflang="ar" href="{{ url('/ar') }}" />
    </url>
    @foreach ($staticPages as $page)
        <url>
            <loc>{{ url("/web/static-pages/{$page->slug}") }}</loc>
            <lastmod>{{ $page->updated_at->toIso8601String() }}</lastmod>
            <changefreq>monthly</changefreq>
            <priority>0.8</priority>
        </url>
    @endforeach
</urlset>