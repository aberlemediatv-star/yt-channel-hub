<?php

declare(strict_types=1);

namespace YtHub;

use Google\Service\YouTube;
use Google\Service\YouTube\Video;
use Google\Service\YouTube\VideoSnippet;
use Google\Service\YouTube\VideoStatus;
use PDO;
use RuntimeException;
use Throwable;

/**
 * Applies bulk metadata edits to owned videos via YouTube Data API (videos.list + videos.update).
 *
 * @phpstan-type BulkOptions array{
 *   privacy?: string,
 *   category_id?: string,
 *   tags_mode?: string,
 *   tags_text?: string,
 *   title_mode?: string,
 *   title_prefix?: string,
 *   title_suffix?: string,
 *   title_find?: string,
 *   title_replace?: string,
 *   desc_mode?: string,
 *   desc_text?: string,
 *   license?: string,
 *   embeddable?: string,
 *   made_for_kids?: string,
 *   public_stats?: string,
 * }
 */
final class YouTubeVideoBulkEditService
{
    public function __construct(
        private PDO $pdo,
        private TokenCipher $cipher,
    ) {
    }

    /**
     * @param list<array{video_id: string, channel_id: int}> $rows Video rows (already scoped to allowed channels).
     * @param BulkOptions $options
     *
     * @return array{ok: int, fail: int, errors: list<string>}
     */
    public function apply(array $rows, array $options): array
    {
        if ($rows === []) {
            return ['ok' => 0, 'fail' => 0, 'errors' => []];
        }

        $byChannel = [];
        foreach ($rows as $row) {
            $cid = (int) $row['channel_id'];
            $vid = (string) $row['video_id'];
            if ($cid <= 0 || ! preg_match('/^[a-zA-Z0-9_-]{11}$/', $vid)) {
                continue;
            }
            if (! isset($byChannel[$cid])) {
                $byChannel[$cid] = [];
            }
            $byChannel[$cid][] = $vid;
        }

        $ok = 0;
        $fail = 0;
        /** @var list<string> $errors */
        $errors = [];

        foreach ($byChannel as $channelDbId => $videoIds) {
            $videoIds = array_values(array_unique($videoIds));
            try {
                $client = $this->createAuthorizedClientForChannel($channelDbId);
            } catch (Throwable $e) {
                foreach ($videoIds as $id) {
                    $fail++;
                    if (count($errors) < 12) {
                        $errors[] = $id.': '.$e->getMessage();
                    }
                }

                continue;
            }
            $youtube = new YouTube($client);
            foreach ($videoIds as $videoId) {
                try {
                    $this->applyOne($youtube, $videoId, $options);
                    $ok++;
                } catch (Throwable $e) {
                    $fail++;
                    if (count($errors) < 12) {
                        $errors[] = $videoId.': '.$e->getMessage();
                    }
                }
                usleep(80_000);
            }
        }

        return ['ok' => $ok, 'fail' => $fail, 'errors' => $errors];
    }

    /**
     * @param BulkOptions $options
     */
    private function applyOne(YouTube $youtube, string $videoId, array $options): void
    {
        $list = $youtube->videos->listVideos('snippet,status', ['id' => $videoId]);
        $items = $list->getItems();
        if ($items === null || $items === []) {
            throw new RuntimeException('Video not found or not accessible.');
        }
        $video = $items[0];
        $snippet = $video->getSnippet();
        $status = $video->getStatus();
        if (! $snippet instanceof VideoSnippet || ! $status instanceof VideoStatus) {
            throw new RuntimeException('Incomplete video resource.');
        }

        $snippetTouched = false;
        $statusTouched = false;

        $privacy = (string) ($options['privacy'] ?? '');
        if ($privacy !== '' && in_array($privacy, ['private', 'unlisted', 'public'], true)) {
            $status->setPrivacyStatus($privacy);
            $statusTouched = true;
        }

        $cat = trim((string) ($options['category_id'] ?? ''));
        if ($cat !== '' && preg_match('/^\d{1,6}$/', $cat)) {
            $snippet->setCategoryId($cat);
            $snippetTouched = true;
        }

        $tagsMode = (string) ($options['tags_mode'] ?? '');
        if ($tagsMode === 'clear') {
            $snippet->setTags([]);
            $snippetTouched = true;
        } elseif ($tagsMode === 'replace' || $tagsMode === 'append') {
            $newTags = $this->parseTags((string) ($options['tags_text'] ?? ''));
            if ($tagsMode === 'replace') {
                $snippet->setTags($newTags);
            } else {
                $existing = $snippet->getTags();
                $merged = [];
                foreach (array_merge($existing, $newTags) as $t) {
                    $t = trim((string) $t);
                    if ($t !== '' && ! in_array($t, $merged, true)) {
                        $merged[] = $t;
                    }
                    if (count($merged) >= 30) {
                        break;
                    }
                }
                $snippet->setTags($merged);
            }
            $snippetTouched = true;
        }

        $titleMode = (string) ($options['title_mode'] ?? '');
        $title = (string) $snippet->getTitle();
        if ($titleMode === 'prefix') {
            $p = (string) ($options['title_prefix'] ?? '');
            if ($p !== '') {
                $snippet->setTitle($this->clipTitle($p.$title));
                $snippetTouched = true;
            }
        } elseif ($titleMode === 'suffix') {
            $s = (string) ($options['title_suffix'] ?? '');
            if ($s !== '') {
                $snippet->setTitle($this->clipTitle($title.$s));
                $snippetTouched = true;
            }
        } elseif ($titleMode === 'find_replace') {
            $find = (string) ($options['title_find'] ?? '');
            $repl = (string) ($options['title_replace'] ?? '');
            if ($find !== '') {
                $snippet->setTitle($this->clipTitle(str_replace($find, $repl, $title)));
                $snippetTouched = true;
            }
        }

        $descMode = (string) ($options['desc_mode'] ?? '');
        $desc = (string) $snippet->getDescription();
        $block = (string) ($options['desc_text'] ?? '');
        if ($descMode === 'prepend' && $block !== '') {
            $snippet->setDescription($this->clipDescription($block."\n\n".$desc));
            $snippetTouched = true;
        } elseif ($descMode === 'append' && $block !== '') {
            $snippet->setDescription($this->clipDescription($desc."\n\n".$block));
            $snippetTouched = true;
        }

        $lic = (string) ($options['license'] ?? '');
        if ($lic === 'youtube' || $lic === 'creativeCommon') {
            $status->setLicense($lic);
            $statusTouched = true;
        }

        $emb = (string) ($options['embeddable'] ?? '');
        if ($emb === '1' || $emb === '0') {
            $status->setEmbeddable($emb === '1');
            $statusTouched = true;
        }

        $mfk = (string) ($options['made_for_kids'] ?? '');
        if ($mfk === '1' || $mfk === '0') {
            $status->setSelfDeclaredMadeForKids($mfk === '1');
            $statusTouched = true;
        }

        $ps = (string) ($options['public_stats'] ?? '');
        if ($ps === '1' || $ps === '0') {
            $status->setPublicStatsViewable($ps === '1');
            $statusTouched = true;
        }

        if (! $snippetTouched && ! $statusTouched) {
            throw new RuntimeException('No changes to apply.');
        }

        $parts = [];
        if ($snippetTouched) {
            $parts[] = 'snippet';
        }
        if ($statusTouched) {
            $parts[] = 'status';
        }
        $out = new Video();
        $out->setId($videoId);
        if ($snippetTouched) {
            $out->setSnippet($snippet);
        }
        if ($statusTouched) {
            $out->setStatus($status);
        }
        $youtube->videos->update(implode(',', $parts), $out, []);
    }

    /** @return list<string> */
    private function parseTags(string $raw): array
    {
        $parts = preg_split('/\s*,\s*/', $raw) ?: [];
        $out = [];
        foreach ($parts as $p) {
            $p = trim((string) $p);
            if ($p === '' || in_array($p, $out, true)) {
                continue;
            }
            $out[] = $p;
            if (count($out) >= 30) {
                break;
            }
        }

        return $out;
    }

    private function clipTitle(string $title): string
    {
        if (function_exists('mb_substr')) {
            return mb_substr($title, 0, 100, 'UTF-8');
        }

        return substr($title, 0, 100);
    }

    private function clipDescription(string $text): string
    {
        if (function_exists('mb_substr')) {
            return mb_substr($text, 0, 5000, 'UTF-8');
        }

        return substr($text, 0, 5000);
    }

    private function createAuthorizedClientForChannel(int $channelDbId): \Google\Client
    {
        $st = $this->pdo->prepare(
            'SELECT c.id, c.youtube_channel_id, o.refresh_token
             FROM channels c
             LEFT JOIN oauth_credentials o ON o.id = c.oauth_credential_id
             WHERE c.id = ? AND c.is_active = 1'
        );
        $st->execute([$channelDbId]);
        $row = $st->fetch();
        if (! $row || empty($row['refresh_token'])) {
            throw new RuntimeException('No OAuth for channel.');
        }
        $refresh = $this->cipher->decrypt((string) $row['refresh_token']);

        $client = app_google_client();
        $client->setDeveloperKey('');
        $client->fetchAccessTokenWithRefreshToken($refresh);
        if ($client->isAccessTokenExpired()) {
            throw new RuntimeException('Access token expired.');
        }

        return $client;
    }
}
