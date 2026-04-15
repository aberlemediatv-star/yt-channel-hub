<?php

declare(strict_types=1);

namespace YtHub;

use PDO;

final class VideoRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    /** @return list<array<string, mixed>> */
    public function latestByChannel(int $channelId, int $limit = 24): array
    {
        $st = $this->pdo->prepare(
            'SELECT video_id, title, thumbnail_url, published_at, view_count, duration_iso
             FROM videos WHERE channel_id = ?
             ORDER BY published_at IS NULL, published_at DESC, id DESC
             LIMIT ' . (int) $limit
        );
        $st->execute([$channelId]);
        return $st->fetchAll();
    }

    /**
     * @param list<int> $channelIds
     * @return list<array<string, mixed>>
     */
    public function recentForChannels(array $channelIds, int $limit = 120): array
    {
        $channelIds = array_values(array_filter(array_map('intval', $channelIds), static fn (int $id) => $id > 0));
        if ($channelIds === []) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($channelIds), '?'));
        $lim = max(1, min(500, $limit));
        $st = $this->pdo->prepare(
            "SELECT v.video_id, v.title, v.thumbnail_url, v.published_at, v.view_count, v.duration_iso,
                    v.channel_id, c.title AS channel_title
             FROM videos v
             INNER JOIN channels c ON c.id = v.channel_id
             WHERE v.channel_id IN ($placeholders)
             ORDER BY v.published_at IS NULL, v.published_at DESC, v.id DESC
             LIMIT {$lim}"
        );
        $st->execute($channelIds);

        return $st->fetchAll();
    }

    /**
     * Resolve video IDs that exist in the DB and belong to one of the given channels.
     *
     * @param list<string> $videoIds
     * @param list<int> $channelIds
     *
     * @return list<array{video_id: string, channel_id: int}>
     */
    public function rowsForVideoIdsInChannels(array $videoIds, array $channelIds): array
    {
        $channelIds = array_values(array_filter(array_map('intval', $channelIds), static fn (int $id) => $id > 0));
        $videoIds = array_values(array_unique(array_filter(array_map('strval', $videoIds), static fn (string $id) => preg_match('/^[a-zA-Z0-9_-]{11}$/', $id))));
        if ($videoIds === [] || $channelIds === []) {
            return [];
        }
        $vPh = implode(',', array_fill(0, count($videoIds), '?'));
        $cPh = implode(',', array_fill(0, count($channelIds), '?'));
        $st = $this->pdo->prepare(
            "SELECT video_id, channel_id FROM videos WHERE video_id IN ($vPh) AND channel_id IN ($cPh)"
        );
        $st->execute([...$videoIds, ...$channelIds]);
        /** @var list<array{video_id: string, channel_id: int|string}> $rows */
        $rows = $st->fetchAll();
        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'video_id' => (string) $r['video_id'],
                'channel_id' => (int) $r['channel_id'],
            ];
        }

        return $out;
    }
}
