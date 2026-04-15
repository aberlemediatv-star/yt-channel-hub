<?php

declare(strict_types=1);

namespace YtHub;

use Google\Client;
use Google\Service\YouTube;
use PDO;
use RuntimeException;
use Throwable;

final class YouTubeDataService
{
    public function __construct(
        private Client $google,
        private string $apiKey,
        private PDO $pdo
    ) {
    }

    /**
     * Lädt Kanal-Meta inkl. uploads_playlist_id und speichert in DB.
     */
    public function ensureChannelMeta(int $channelDbId, string $youtubeChannelId): void
    {
        $this->google->setDeveloperKey($this->apiKey);
        $yt = new YouTube($this->google);
        $resp = Retry::withBackoff(function () use ($yt, $youtubeChannelId) {
            return $yt->channels->listChannels('snippet,contentDetails', [
                'id' => [$youtubeChannelId],
            ]);
        });
        $items = $resp->getItems();
        if ($items === [] || $items[0] === null) {
            throw new RuntimeException('Kanal nicht gefunden: ' . $youtubeChannelId);
        }
        $ch = $items[0];
        $uploads = $ch->getContentDetails()?->getRelatedPlaylists()?->getUploads();
        $title = $ch->getSnippet()?->getTitle() ?? '';
        $st = $this->pdo->prepare(
            'UPDATE channels SET title = ?, uploads_playlist_id = ? WHERE id = ?'
        );
        $st->execute([$title, $uploads, $channelDbId]);
    }

    /**
     * Synchronisiert alle Videos der Upload-Playlist in die Tabelle videos.
     */
    public function syncVideosForChannel(int $channelDbId, string $youtubeChannelId): int
    {
        $this->ensureChannelMeta($channelDbId, $youtubeChannelId);
        $st = $this->pdo->prepare('SELECT uploads_playlist_id FROM channels WHERE id = ?');
        $st->execute([$channelDbId]);
        $row = $st->fetch();
        $playlistId = $row['uploads_playlist_id'] ?? null;
        if (!$playlistId) {
            throw new RuntimeException('uploads_playlist_id fehlt für Kanal ' . $channelDbId);
        }

        $this->google->setDeveloperKey($this->apiKey);
        $yt = new YouTube($this->google);

        $videoIds = [];
        $pageToken = null;
        do {
            $pl = Retry::withBackoff(function () use ($yt, $playlistId, $pageToken) {
                return $yt->playlistItems->listPlaylistItems('snippet,contentDetails', [
                    'playlistId' => $playlistId,
                    'maxResults' => 50,
                    'pageToken' => $pageToken,
                ]);
            });
            foreach ($pl->getItems() ?? [] as $item) {
                $vid = $item->getContentDetails()?->getVideoId();
                if ($vid) {
                    $videoIds[] = $vid;
                }
            }
            $pageToken = $pl->getNextPageToken();
        } while ($pageToken);

        $count = 0;
        foreach (array_chunk($videoIds, 50) as $chunk) {
            $resp = Retry::withBackoff(function () use ($yt, $chunk) {
                return $yt->videos->listVideos('snippet,statistics,contentDetails', [
                    'id' => implode(',', $chunk),
                ]);
            });
            foreach ($resp->getItems() ?? [] as $v) {
                $vid = $v->getId();
                $sn = $v->getSnippet();
                $st = $v->getStatistics();
                $thumb = $sn?->getThumbnails()?->getHigh()?->getUrl()
                    ?? $sn?->getThumbnails()?->getMedium()?->getUrl()
                    ?? '';
                $published = $sn?->getPublishedAt();
                $pubStr = $published ? $published->format('Y-m-d H:i:s') : null;
                $views = (int) ($st?->getViewCount() ?? 0);
                $duration = $v->getContentDetails()?->getDuration();

                $rawJson = null;
                try {
                    $rawJson = json_encode($v, JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE);
                } catch (Throwable) {
                    $rawJson = null;
                }

                $ins = $this->pdo->prepare(
                    'INSERT INTO videos (channel_id, video_id, title, description, published_at, thumbnail_url, view_count, duration_iso, raw_json)
                     VALUES (?,?,?,?,?,?,?,?,?)
                     ON DUPLICATE KEY UPDATE
                       title = VALUES(title),
                       description = VALUES(description),
                       published_at = VALUES(published_at),
                       thumbnail_url = VALUES(thumbnail_url),
                       view_count = VALUES(view_count),
                       duration_iso = VALUES(duration_iso),
                       raw_json = VALUES(raw_json)'
                );
                $ins->execute([
                    $channelDbId,
                    $vid,
                    (string) ($sn?->getTitle() ?? ''),
                    $sn?->getDescription(),
                    $pubStr,
                    $thumb,
                    $views,
                    $duration,
                    $rawJson,
                ]);
                $count++;
            }
        }

        $up = $this->pdo->prepare(
            'UPDATE channels SET last_video_sync_at = NOW(), last_video_sync_error = NULL WHERE id = ?'
        );
        $up->execute([$channelDbId]);

        return $count;
    }
}
