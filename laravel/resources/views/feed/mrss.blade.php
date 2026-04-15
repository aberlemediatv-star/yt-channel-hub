<?php
/** @var array<string, mixed> $channel */
/** @var list<array<string, mixed>> $videos */
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
    <title>{{ e($channel['title']) }}</title>
    <link>https://www.youtube.com/channel/{{ e($channel['youtube_channel_id']) }}</link>
    <description>Videos von {{ e($channel['title']) }} — bereitgestellt als MRSS-Feed für FAST-Plattformen.</description>
    <language>de</language>
    <lastBuildDate>{{ $buildDate }}</lastBuildDate>
    <atom:link href="{{ e($feedUrl) }}" rel="self" type="application/rss+xml"/>
    <image>
        <url>{{ $appUrl }}/assets/logo.png</url>
        <title>{{ e($channel['title']) }}</title>
        <link>https://www.youtube.com/channel/{{ e($channel['youtube_channel_id']) }}</link>
    </image>
@foreach($videos as $v)
@php
    $ytUrl = 'https://www.youtube.com/watch?v=' . rawurlencode((string) $v['video_id']);
    $seconds = ISODurationHelper::toSeconds($v['duration_iso'] ?? null);
    $thumb = $v['thumbnail_url'] ?? '';
    $pubDate = $v['published_at'] ? (new DateTime($v['published_at']))->format('D, d M Y H:i:s') . ' +0000' : $buildDate;
    $desc = trim((string) ($v['description'] ?? ''));
    if ($desc === '') { $desc = $v['title']; }
@endphp
    <item>
        <title>{{ e($v['title']) }}</title>
        <link>{{ e($ytUrl) }}</link>
        <guid isPermaLink="true">{{ e($ytUrl) }}</guid>
        <pubDate>{{ $pubDate }}</pubDate>
        <description>{{ e(mb_substr($desc, 0, 1000, 'UTF-8')) }}</description>
        <media:content url="{{ e($ytUrl) }}"
                       type="video/mp4"
                       duration="{{ $seconds }}"
                       isDefault="true">
            <media:title type="plain">{{ e($v['title']) }}</media:title>
            <media:description type="plain">{{ e(mb_substr($desc, 0, 1000, 'UTF-8')) }}</media:description>
@if($thumb !== '')
            <media:thumbnail url="{{ e($thumb) }}" />
@endif
            <media:category scheme="urn:ythub:channel">{{ e($channel['title']) }}</media:category>
        </media:content>
@if($thumb !== '')
        <media:thumbnail url="{{ e($thumb) }}" />
@endif
        <dcterms:valid>start={{ str_replace(' ', 'T', $v['published_at'] ?? now()->toDateTimeString()) }}+00:00; scheme=W3C-DTF</dcterms:valid>
    </item>
@endforeach
</channel>
</rss>
