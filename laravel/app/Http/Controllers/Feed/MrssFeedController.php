<?php

namespace App\Http\Controllers\Feed;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use YtHub\ChannelRepository;
use YtHub\Db;
use YtHub\VideoRepository;

final class MrssFeedController extends Controller
{
    /**
     * MRSS feed for a single channel identified by slug.
     * Designed for FAST channel providers (Pluto TV, Samsung TV Plus, Rakuten TV, etc.).
     */
    public function show(Request $request, string $slug): Response
    {
        $pdo = Db::pdo();
        $channelRepo = new ChannelRepository($pdo);
        $videoRepo = new VideoRepository($pdo);

        $channel = $this->findChannelBySlug($channelRepo, $slug);
        if ($channel === null) {
            abort(404, 'Channel nicht gefunden.');
        }

        $limit = max(1, min(500, (int) $request->query('limit', 200)));
        $videos = $this->loadVideosForFeed($pdo, (int) $channel['id'], $limit);

        $appUrl = rtrim((string) config('app.url', ''), '/');
        $feedUrl = $appUrl.'/feed/mrss/'.rawurlencode($slug);

        $xml = view('feed.mrss', [
            'channel' => $channel,
            'videos' => $videos,
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
     * Index page listing all active channels with their feed URLs.
     */
    public function index(): Response
    {
        $pdo = Db::pdo();
        $channelRepo = new ChannelRepository($pdo);
        $channels = $channelRepo->listActiveForFrontend();

        $appUrl = rtrim((string) config('app.url', ''), '/');

        $xml = view('feed.mrss-index', [
            'channels' => $channels,
            'appUrl' => $appUrl,
            'buildDate' => gmdate('D, d M Y H:i:s').' +0000',
        ])->render();

        return new Response($xml, 200, [
            'Content-Type' => 'application/rss+xml; charset=utf-8',
            'Cache-Control' => 'public, max-age=900, s-maxage=1800',
        ]);
    }

    private function findChannelBySlug(ChannelRepository $repo, string $slug): ?array
    {
        foreach ($repo->listActiveForFrontend() as $ch) {
            if (($ch['slug'] ?? '') === $slug) {
                return $ch;
            }
        }

        return null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function loadVideosForFeed(\PDO $pdo, int $channelId, int $limit): array
    {
        $st = $pdo->prepare(
            'SELECT video_id, title, description, thumbnail_url, published_at,
                    view_count, duration_iso
             FROM videos
             WHERE channel_id = ? AND published_at IS NOT NULL
             ORDER BY published_at DESC
             LIMIT '.max(1, $limit)
        );
        $st->execute([$channelId]);

        return $st->fetchAll();
    }
}
