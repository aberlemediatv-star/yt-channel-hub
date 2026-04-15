<?php

declare(strict_types=1);

namespace YtHub;

use PDO;

final class ChannelRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    /** @return list<array<string, mixed>> */
    public function listActiveForFrontend(): array
    {
        $st = $this->pdo->query(
            'SELECT id, slug, title, youtube_channel_id, sort_order
             FROM channels WHERE is_active = 1 ORDER BY sort_order ASC, id ASC'
        );
        return $st->fetchAll();
    }

    /** @return list<array<string, mixed>> */
    public function listActiveForBackend(): array
    {
        $st = $this->pdo->query(
            'SELECT c.id, c.slug, c.title, c.youtube_channel_id, c.oauth_credential_id, o.refresh_token
             FROM channels c
             LEFT JOIN oauth_credentials o ON o.id = c.oauth_credential_id
             WHERE c.is_active = 1
             ORDER BY c.sort_order ASC, c.id ASC'
        );
        return $st->fetchAll();
    }

    /** @return list<array<string, mixed>> */
    public function listAllAdmin(): array
    {
        $st = $this->pdo->query(
            'SELECT id, slug, title, youtube_channel_id, uploads_playlist_id, oauth_credential_id,
                    sort_order, is_active,
                    last_video_sync_at, last_video_sync_error,
                    last_analytics_sync_at, last_analytics_sync_error,
                    created_at, updated_at
             FROM channels ORDER BY sort_order ASC, id ASC'
        );
        return $st->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function findById(int $id): ?array
    {
        $st = $this->pdo->prepare('SELECT * FROM channels WHERE id = ?');
        $st->execute([$id]);
        $row = $st->fetch();
        return $row ?: null;
    }

    public function insert(
        string $slug,
        string $title,
        string $youtubeChannelId,
        int $sortOrder,
        bool $isActive
    ): int {
        $st = $this->pdo->prepare(
            'INSERT INTO channels (slug, title, youtube_channel_id, sort_order, is_active)
             VALUES (?,?,?,?,?)'
        );
        $st->execute([$slug, $title, $youtubeChannelId, $sortOrder, $isActive ? 1 : 0]);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(
        int $id,
        string $slug,
        string $title,
        string $youtubeChannelId,
        int $sortOrder,
        bool $isActive
    ): void {
        $st = $this->pdo->prepare(
            'UPDATE channels SET slug = ?, title = ?, youtube_channel_id = ?, sort_order = ?, is_active = ? WHERE id = ?'
        );
        $st->execute([$slug, $title, $youtubeChannelId, $sortOrder, $isActive ? 1 : 0, $id]);
    }

    public function delete(int $id): void
    {
        $st = $this->pdo->prepare('DELETE FROM channels WHERE id = ?');
        $st->execute([$id]);
    }

    public function getRefreshTokenForChannel(int $channelId): ?string
    {
        $st = $this->pdo->prepare(
            'SELECT o.refresh_token FROM channels c
             INNER JOIN oauth_credentials o ON o.id = c.oauth_credential_id
             WHERE c.id = ?'
        );
        $st->execute([$channelId]);
        $row = $st->fetch();
        return $row ? (string) $row['refresh_token'] : null;
    }
}
