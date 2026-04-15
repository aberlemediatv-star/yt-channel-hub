<?php

namespace App\Http\Controllers\Feed;

use App\Http\Controllers\Controller;
use App\Models\AdvancedFeed;
use Illuminate\Http\Response;
use YtHub\ChannelRepository;
use YtHub\Db;

final class AdvancedMrssFeedController extends Controller
{
    public function show(string $slug): Response
    {
        $feed = AdvancedFeed::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->first();

        if ($feed === null) {
            abort(404, 'Feed nicht gefunden oder inaktiv.');
        }

        $pdo = Db::pdo();
        $channel = (new ChannelRepository($pdo))->findById($feed->channel_id);
        if ($channel === null) {
            abort(404, 'Kanal nicht gefunden.');
        }

        $items = $feed->items()->get();
        $videoIds = $items->pluck('youtube_video_id')->toArray();
        $ytVideos = $this->loadYtVideosByIds($pdo, $videoIds);

        $appUrl = rtrim((string) config('app.url', ''), '/');
        $feedUrl = $appUrl.'/feed/advanced/'.rawurlencode($slug);

        $xml = view('feed.mrss-advanced', [
            'feed' => $feed,
            'channel' => $channel,
            'items' => $items,
            'ytVideos' => $ytVideos,
            'feedUrl' => $feedUrl,
            'appUrl' => $appUrl,
            'buildDate' => gmdate('D, d M Y H:i:s').' +0000',
        ])->render();

        return new Response($xml, 200, [
            'Content-Type' => 'application/rss+xml; charset=utf-8',
            'Cache-Control' => 'public, max-age=900, s-maxage=1800',
        ]);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function loadYtVideosByIds(\PDO $pdo, array $videoIds): array
    {
        if ($videoIds === []) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($videoIds), '?'));
        $st = $pdo->prepare(
            "SELECT video_id, title, description, thumbnail_url, published_at, duration_iso
             FROM videos WHERE video_id IN ($placeholders)"
        );
        $st->execute(array_values($videoIds));
        $map = [];
        foreach ($st->fetchAll() as $row) {
            $map[$row['video_id']] = $row;
        }

        return $map;
    }
}
