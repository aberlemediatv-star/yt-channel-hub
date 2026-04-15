<?php
/** @var list<array<string, mixed>> $channels */
/** @var string $appUrl */
/** @var string $buildDate */
?>{!! '<?xml version="1.0" encoding="UTF-8"?>' !!}
<rss version="2.0"
     xmlns:atom="http://www.w3.org/2005/Atom">
<channel>
    <title>YT Channel Hub — MRSS Feeds</title>
    <link>{{ e($appUrl) }}</link>
    <description>Verfügbare MRSS-Feeds für FAST-Plattformen.</description>
    <language>de</language>
    <lastBuildDate>{{ $buildDate }}</lastBuildDate>
@foreach($channels as $ch)
    <item>
        <title>{{ e($ch['title']) }} — MRSS Feed</title>
        <link>{{ e($appUrl . '/feed/mrss/' . rawurlencode((string) $ch['slug'])) }}</link>
        <guid isPermaLink="true">{{ e($appUrl . '/feed/mrss/' . rawurlencode((string) $ch['slug'])) }}</guid>
        <description>MRSS-Feed für {{ e($ch['title']) }} ({{ e($ch['youtube_channel_id']) }})</description>
    </item>
@endforeach
</channel>
</rss>
