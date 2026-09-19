<?= '<?xml version="1.0" encoding="UTF-8"?>'."\n" ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url><loc>{{ url('/') }}</loc><changefreq>weekly</changefreq><priority>1.0</priority></url>
    <url><loc>{{ url('/blog') }}</loc><changefreq>weekly</changefreq><priority>0.9</priority></url>
    <url><loc>{{ url('/reise') }}</loc><changefreq>weekly</changefreq><priority>0.8</priority></url>
    <url><loc>{{ url('/newsletter') }}</loc><changefreq>monthly</changefreq><priority>0.5</priority></url>
    @foreach ($posts as $post)
        <url>
            <loc>{{ url('/blog/'.$post->slug) }}</loc>
            <lastmod>{{ $post->updated_at?->toAtomString() }}</lastmod>
            <changefreq>monthly</changefreq>
            <priority>0.8</priority>
        </url>
    @endforeach
    <url><loc>{{ url('/impressum') }}</loc><priority>0.2</priority></url>
    <url><loc>{{ url('/datenschutz') }}</loc><priority>0.2</priority></url>
</urlset>
