<?php
/** @var \App\Models\AdvancedFeed $feed */
/** @var array<string, mixed> $channel */
/** @var \Illuminate\Database\Eloquent\Collection<int, \App\Models\AdvancedFeedItem> $items */
/** @var array<string, array<string, mixed>> $ytVideos */
/** @var string $feedUrl */
/** @var string $appUrl */
/** @var string $buildDate */

use App\Support\Feed\ISODurationHelper;
?>{!! '<?xml version="1.0" encoding="UTF-8"?>' !!}
<rss version="2.0"
     xmlns:media="http://search.yahoo.com/mrss/"
     xmlns:dcterms="http://purl.org/dc/terms/"
     xmlns:atom="http://www.w3.org/2005/Atom">
<channel>
    <title>{{ e($feed->title) }}</title>
    <link>https://www.youtube.com/channel/{{ e($channel['youtube_channel_id']) }}</link>
    <description>Kuratierter Feed: {{ e($feed->title) }} ({{ e($channel['title']) }}) — Sprache: {{ e($feed->language) }}</description>
    <language>{{ e($feed->language) }}</language>
    <lastBuildDate>{{ $buildDate }}</lastBuildDate>
    <atom:link href="{{ e($feedUrl) }}" rel="self" type="application/rss+xml"/>
@foreach($items as $item)
@php
    $yt = $ytVideos[$item->youtube_video_id] ?? null;
    if ($yt === null) continue;

    $ytUrl = 'https://www.youtube.com/watch?v=' . rawurlencode($item->youtube_video_id);
    $seconds = ISODurationHelper::toSeconds($yt['duration_iso'] ?? null);
    $thumb = $item->tmdb_poster_url ?? $yt['thumbnail_url'] ?? '';
    $title = $item->effectiveTitle() ?? $yt['title'];
    $desc = $item->effectiveDescription() ?? trim((string) ($yt['description'] ?? ''));
    if ($desc === '') { $desc = $title; }
    $pubDate = $yt['published_at']
        ? (new DateTime((string) $yt['published_at'], new DateTimeZone('UTC')))->format('D, d M Y H:i:s') . ' +0000'
        : $buildDate;
@endphp
    <item>
        <title>{{ e($title) }}</title>
        <link>{{ e($ytUrl) }}</link>
        <guid isPermaLink="true">{{ e($ytUrl) }}</guid>
        <pubDate>{{ $pubDate }}</pubDate>
        <description>{{ e(mb_substr($desc, 0, 1000, 'UTF-8')) }}</description>
        <media:content url="{{ e($ytUrl) }}"
                       type="video/mp4"
                       duration="{{ $seconds }}"
                       isDefault="true">
            <media:title type="plain">{{ e($title) }}</media:title>
            <media:description type="plain">{{ e(mb_substr($desc, 0, 1000, 'UTF-8')) }}</media:description>
@if($thumb !== '')
            <media:thumbnail url="{{ e($thumb) }}" />
@endif
            <media:category scheme="urn:ythub:feed">{{ e($feed->title) }}</media:category>
@if($item->tmdb_id)
            <media:category scheme="urn:tmdb:{{ e($item->tmdb_type) }}">{{ $item->tmdb_id }}</media:category>
@endif
        </media:content>
@if($thumb !== '')
        <media:thumbnail url="{{ e($thumb) }}" />
@endif
        <dcterms:valid>start={{ str_replace(' ', 'T', $yt['published_at'] ?? now()->toDateTimeString()) }}+00:00; scheme=W3C-DTF</dcterms:valid>
    </item>
@endforeach
</channel>
</rss>
