<?php

declare(strict_types=1);

namespace YtHub;

use PDO;

final class StaffRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    /** @return list<array<string, mixed>> */
    public function listAll(): array
    {
        $st = $this->pdo->query(
            'SELECT id, username, created_at FROM staff_users ORDER BY username ASC'
        );

        return $st->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function findById(int $id): ?array
    {
        $st = $this->pdo->prepare('SELECT * FROM staff_users WHERE id = ?');
        $st->execute([$id]);
        $row = $st->fetch();

        return $row ?: null;
    }

    /** @return array<string, mixed>|null */
    public function findByUsername(string $username): ?array
    {
        $st = $this->pdo->prepare('SELECT * FROM staff_users WHERE username = ? LIMIT 1');
        $st->execute([$username]);
        $row = $st->fetch();

        return $row ?: null;
    }

    public function create(string $username, string $passwordHash): int
    {
        $st = $this->pdo->prepare(
            'INSERT INTO staff_users (username, password_hash) VALUES (?, ?)'
        );
        $st->execute([$username, $passwordHash]);

        return (int) $this->pdo->lastInsertId();
    }

    public function delete(int $id): void
    {
        $st = $this->pdo->prepare('DELETE FROM staff_users WHERE id = ?');
        $st->execute([$id]);
    }

    /**
     * @return array<string, bool>
     */
    public function getMergedModules(int $staffId): array
    {
        $row = $this->findById($staffId);
        if ($row === null) {
            return StaffModule::defaults();
        }
        $raw = $row['modules_json'] ?? null;
        $decoded = [];
        if (is_string($raw) && $raw !== '') {
            try {
                $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException) {
                $decoded = [];
            }
        } elseif (is_array($raw)) {
            $decoded = $raw;
        }
        if (!is_array($decoded)) {
            $decoded = [];
        }

        return StaffModule::mergeDefaults($decoded);
    }

    /**
     * @param array<string, bool> $modules
     */
    public function setModules(int $staffId, array $modules): void
    {
        $clean = [];
        foreach (StaffModule::allKeys() as $k) {
            $clean[$k] = !empty($modules[$k]);
        }
        $json = json_encode($clean, JSON_THROW_ON_ERROR);
        $st = $this->pdo->prepare('UPDATE staff_users SET modules_json = ? WHERE id = ?');
        $st->execute([$json, $staffId]);
    }

    public function hasModule(int $staffId, string $key): bool
    {
        $m = $this->getMergedModules($staffId);

        return !empty($m[$key]);
    }

    /** Nur freigegebene (nicht gesperrte) Kanal-IDs. */
    /** @return list<int> */
    public function allowedChannelIds(int $staffId): array
    {
        $st = $this->pdo->prepare(
            'SELECT channel_id FROM staff_channel_access
             WHERE staff_id = ? AND blocked = 0
             ORDER BY channel_id'
        );
        $st->execute([$staffId]);
        $out = [];
        foreach ($st->fetchAll() as $r) {
            $out[] = (int) $r['channel_id'];
        }

        return $out;
    }

    /**
     * @return array{allowed:int, blocked:int}
     */
    public function channelAccessStats(int $staffId): array
    {
        $st = $this->pdo->prepare(
            'SELECT blocked, COUNT(*) AS n FROM staff_channel_access WHERE staff_id = ? GROUP BY blocked'
        );
        $st->execute([$staffId]);
        $allowed = 0;
        $blocked = 0;
        foreach ($st->fetchAll() as $r) {
            if ((int) $r['blocked'] === 1) {
                $blocked = (int) $r['n'];
            } else {
                $allowed = (int) $r['n'];
            }
        }

        return ['allowed' => $allowed, 'blocked' => $blocked];
    }

    /**
     * Zustand pro Kanal für Admin-Maske: none | allow | block.
     *
     * @return array<int, string>
     */
    public function getChannelAccessStates(int $staffId): array
    {
        $st = $this->pdo->prepare(
            'SELECT channel_id, blocked FROM staff_channel_access WHERE staff_id = ?'
        );
        $st->execute([$staffId]);
        $map = [];
        foreach ($st->fetchAll() as $r) {
            $cid = (int) $r['channel_id'];
            $map[$cid] = (int) $r['blocked'] === 1 ? 'block' : 'allow';
        }

        return $map;
    }

    /**
     * @param array<int, string> $channelIdToState none|allow|block
     */
    public function setChannelAccessMap(int $staffId, array $channelIdToState): void
    {
        $this->pdo->beginTransaction();
        try {
            $del = $this->pdo->prepare('DELETE FROM staff_channel_access WHERE staff_id = ?');
            $del->execute([$staffId]);
            $ins = $this->pdo->prepare(
                'INSERT INTO staff_channel_access (staff_id, channel_id, blocked) VALUES (?, ?, ?)'
            );
            foreach ($channelIdToState as $cid => $state) {
                $cid = (int) $cid;
                if ($cid <= 0 || $state === 'none') {
                    continue;
                }
                if (!in_array($state, ['allow', 'block'], true)) {
                    continue;
                }
                $blocked = $state === 'block' ? 1 : 0;
                $ins->execute([$staffId, $cid, $blocked]);
            }
            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Kanäle mit Titel für Mitarbeiter-Dashboard (nur nicht gesperrte, aktive Kanäle).
     *
     * @return list<array<string, mixed>>
     */
    public function listChannelsForStaff(int $staffId): array
    {
        $st = $this->pdo->prepare(
            'SELECT c.id, c.slug, c.title, c.youtube_channel_id
             FROM channels c
             INNER JOIN staff_channel_access a ON a.channel_id = c.id AND a.staff_id = ? AND a.blocked = 0
             WHERE c.is_active = 1
             ORDER BY c.sort_order ASC, c.id ASC'
        );
        $st->execute([$staffId]);

        return $st->fetchAll();
    }

    public function staffMayAccessChannel(int $staffId, int $channelId): bool
    {
        if ($staffId <= 0 || $channelId <= 0) {
            return false;
        }
        $st = $this->pdo->prepare(
            'SELECT 1 FROM staff_channel_access
             WHERE staff_id = ? AND channel_id = ? AND blocked = 0
             LIMIT 1'
        );
        $st->execute([$staffId, $channelId]);

        return (bool) $st->fetchColumn();
    }

}
