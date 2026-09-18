<?= '<?xml version="1.0" encoding="UTF-8"?>'."\n" ?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
    <channel>
        <title>Wandermäuse</title>
        <link>{{ url('/') }}</link>
        <atom:link href="{{ route('feed') }}" rel="self" type="application/rss+xml"/>
        <description>Ein Reiseblog über unsere Reise durch Süd- und Mittelamerika.</description>
        <language>de-DE</language>
        @if ($posts->isNotEmpty())
            <lastBuildDate>{{ $posts->first()->published_at?->toRfc2822String() }}</lastBuildDate>
        @endif
        @foreach ($posts as $post)
            <item>
                <title>{{ $post->title }}</title>
                <link>{{ url('/blog/'.$post->slug) }}</link>
                <guid isPermaLink="true">{{ url('/blog/'.$post->slug) }}</guid>
                <pubDate>{{ $post->published_at?->toRfc2822String() }}</pubDate>
                <description>{{ $post->excerpt }}</description>
            </item>
        @endforeach
    </channel>
</rss>
