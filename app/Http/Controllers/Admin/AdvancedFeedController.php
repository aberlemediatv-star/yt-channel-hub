<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdvancedFeed;
use App\Models\AdvancedFeedItem;
use App\Services\Tmdb\TmdbClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use YtHub\ChannelRepository;
use YtHub\Db;
use YtHub\Lang;

final class AdvancedFeedController extends Controller
{
    public function index(Request $request): View
    {
        Lang::init();
        $pdo = Db::pdo();
        $channels = (new ChannelRepository($pdo))->listAllAdmin();
        $channelMap = [];
        foreach ($channels as $ch) {
            $channelMap[(int) $ch['id']] = $ch['title'];
        }

        return view('admin.advanced-feeds.index', [
            'token' => (string) $request->query('token', ''),
            'feeds' => AdvancedFeed::query()->with('items')->orderByDesc('id')->get(),
            'channelMap' => $channelMap,
            'appUrl' => rtrim((string) config('app.url', ''), '/'),
        ]);
    }

    public function create(Request $request): View
    {
        Lang::init();
        $pdo = Db::pdo();
        $channels = (new ChannelRepository($pdo))->listAllAdmin();

        return view('admin.advanced-feeds.edit', [
            'token' => (string) $request->query('token', ''),
            'feed' => null,
            'channels' => $channels,
            'tmdbConfigured' => app(TmdbClient::class)->isConfigured(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'channel_id' => ['required', 'integer', 'min:1'],
            'language' => ['required', 'string', 'max:8'],
            'tmdb_enabled' => ['nullable', 'in:1'],
            'is_active' => ['nullable', 'in:1'],
        ]);

        $slug = Str::slug($data['title']);
        $existing = AdvancedFeed::query()->where('slug', $slug)->first();
        if ($existing) {
            $slug .= '-'.Str::random(4);
        }

        $feed = AdvancedFeed::query()->create([
            'slug' => $slug,
            'title' => $data['title'],
            'channel_id' => (int) $data['channel_id'],
            'language' => $data['language'],
            'tmdb_enabled' => isset($data['tmdb_enabled']),
            'is_active' => isset($data['is_active']),
        ]);

        $token = (string) $request->query('token', '');

        return redirect()->to('/admin/advanced-feeds/'.$feed->id.'/edit?token='.urlencode($token))
            ->with('status', 'Feed erstellt.');
    }

    public function edit(Request $request, AdvancedFeed $feed): View
    {
        Lang::init();
        $pdo = Db::pdo();
        $channels = (new ChannelRepository($pdo))->listAllAdmin();

        $items = $feed->items()->get();
        $videoIds = $items->pluck('youtube_video_id')->toArray();
        $ytVideos = $this->loadYtVideosByIds($pdo, $videoIds);

        return view('admin.advanced-feeds.edit', [
            'token' => (string) $request->query('token', ''),
            'feed' => $feed,
            'items' => $items,
            'ytVideos' => $ytVideos,
            'channels' => $channels,
            'tmdbConfigured' => app(TmdbClient::class)->isConfigured(),
        ]);
    }

    public function update(Request $request, AdvancedFeed $feed): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'channel_id' => ['required', 'integer', 'min:1'],
            'language' => ['required', 'string', 'max:8'],
            'tmdb_enabled' => ['nullable', 'in:1'],
            'is_active' => ['nullable', 'in:1'],
        ]);

        $feed->update([
            'title' => $data['title'],
            'channel_id' => (int) $data['channel_id'],
            'language' => $data['language'],
            'tmdb_enabled' => isset($data['tmdb_enabled']),
            'is_active' => isset($data['is_active']),
        ]);

        $token = (string) $request->query('token', '');

        return redirect()->to('/admin/advanced-feeds/'.$feed->id.'/edit?token='.urlencode($token))
            ->with('status', 'Feed aktualisiert.');
    }

    public function destroy(Request $request, AdvancedFeed $feed): RedirectResponse
    {
        $feed->delete();
        $token = (string) $request->query('token', '');

        return redirect()->to('/admin/advanced-feeds?token='.urlencode($token))
            ->with('status', 'Feed gelöscht.');
    }

    /** JSON: available videos for this feed's channel. */
    public function availableVideos(Request $request, AdvancedFeed $feed): JsonResponse
    {
        $pdo = Db::pdo();
        $st = $pdo->prepare(
            'SELECT video_id, title, thumbnail_url, published_at, duration_iso
             FROM videos WHERE channel_id = ? AND published_at IS NOT NULL
             ORDER BY published_at DESC LIMIT 500'
        );
        $st->execute([$feed->channel_id]);

        return response()->json($st->fetchAll());
    }

    /** Add a video to the feed. */
    public function addItem(Request $request, AdvancedFeed $feed): RedirectResponse
    {
        $data = $request->validate([
            'youtube_video_id' => ['required', 'string', 'max:32'],
        ]);

        $maxSort = (int) $feed->items()->max('sort_order');

        AdvancedFeedItem::query()->firstOrCreate(
            ['advanced_feed_id' => $feed->id, 'youtube_video_id' => $data['youtube_video_id']],
            ['sort_order' => $maxSort + 1],
        );

        $token = (string) $request->query('token', '');

        return redirect()->to('/admin/advanced-feeds/'.$feed->id.'/edit?token='.urlencode($token))
            ->with('status', 'Video hinzugefügt.');
    }

    /** Remove a video from the feed. */
    public function removeItem(Request $request, AdvancedFeed $feed, AdvancedFeedItem $item): RedirectResponse
    {
        $msg = 'Video gehört nicht zu diesem Feed.';
        if ($item->advanced_feed_id === $feed->id) {
            $item->delete();
            $msg = 'Video entfernt.';
        }

        $token = (string) $request->query('token', '');

        return redirect()->to('/admin/advanced-feeds/'.$feed->id.'/edit?token='.urlencode($token))
            ->with('status', $msg);
    }

    /** TMDB search (JSON). */
    public function tmdbSearch(Request $request, AdvancedFeed $feed): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        if ($q === '') {
            return response()->json([]);
        }
        $client = app(TmdbClient::class);

        return response()->json($client->search($q, $feed->language));
    }

    /** Apply TMDB data to an item. */
    public function tmdbApply(Request $request, AdvancedFeed $feed, AdvancedFeedItem $item): RedirectResponse
    {
        $data = $request->validate([
            'tmdb_id' => ['required', 'integer', 'min:1'],
            'tmdb_type' => ['required', 'in:movie,tv'],
        ]);

        $client = app(TmdbClient::class);
        $details = $client->details((int) $data['tmdb_id'], $data['tmdb_type'], $feed->language);

        if ($details === null) {
            $token = (string) $request->query('token', '');

            return redirect()->to('/admin/advanced-feeds/'.$feed->id.'/edit?token='.urlencode($token))
                ->with('status', 'TMDB hat keine Daten zurückgegeben.');
        }

        $item->update([
            'tmdb_id' => $details['id'],
            'tmdb_type' => $details['type'],
            'tmdb_title' => $details['title'],
            'tmdb_description' => $details['overview'],
            'tmdb_poster_url' => $details['poster'],
            'tmdb_language' => $feed->language,
        ]);

        $token = (string) $request->query('token', '');

        return redirect()->to('/admin/advanced-feeds/'.$feed->id.'/edit?token='.urlencode($token))
            ->with('status', 'TMDB-Daten übernommen.');
    }

    /** Clear TMDB data from an item. */
    public function tmdbClear(Request $request, AdvancedFeed $feed, AdvancedFeedItem $item): RedirectResponse
    {
        if ($item->advanced_feed_id === $feed->id) {
            $item->update([
                'tmdb_id' => null,
                'tmdb_type' => null,
                'tmdb_title' => null,
                'tmdb_description' => null,
                'tmdb_poster_url' => null,
                'tmdb_language' => null,
            ]);
        }

        $token = (string) $request->query('token', '');

        return redirect()->to('/admin/advanced-feeds/'.$feed->id.'/edit?token='.urlencode($token))
            ->with('status', 'TMDB-Daten entfernt.');
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
