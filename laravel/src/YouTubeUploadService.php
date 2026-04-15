<?php

declare(strict_types=1);

namespace YtHub;

use Google\Http\MediaFileUpload;
use Google\Service\YouTube\Caption;
use Google\Service\YouTube\CaptionSnippet;
use Google\Service\YouTube\Video;
use Google\Service\YouTube\VideoLocalization;
use Google\Service\YouTube\VideoSnippet;
use Google\Service\YouTube\VideoStatus;
use Google\Service\YouTube;
use PDO;
use RuntimeException;

final class YouTubeUploadService
{
    public function __construct(
        private PDO $pdo,
        private TokenCipher $cipher
    ) {
    }

    /**
     * OAuth-Client für einen Kanal (youtube.upload).
     */
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
        if (!$row || empty($row['refresh_token'])) {
            throw new RuntimeException('Kein OAuth-Refresh-Token für diesen Kanal. Admin: OAuth verbinden.');
        }
        $refresh = $this->cipher->decrypt((string) $row['refresh_token']);

        $client = app_google_client();
        $client->setDeveloperKey('');
        $client->fetchAccessTokenWithRefreshToken($refresh);
        if ($client->isAccessTokenExpired()) {
            throw new RuntimeException('Access-Token ungültig — Kanal in der Verwaltung per OAuth erneut verbinden (u. a. youtube.upload, youtube.force-ssl).');
        }

        return $client;
    }

    /**
     * Custom-Thumbnail für ein Video setzen (JPEG/PNG, YouTube-Vorgaben beachten).
     */
    public function setThumbnailFromFile(int $channelDbId, string $youtubeVideoId, string $absolutePath): void
    {
        if (!is_readable($absolutePath)) {
            throw new RuntimeException('Thumbnail-Datei nicht lesbar.');
        }
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($absolutePath) ?: '';
        $allowed = ['image/jpeg', 'image/png'];
        if (!in_array($mime, $allowed, true)) {
            throw new RuntimeException('Thumbnail: nur JPEG oder PNG.');
        }
        if (!preg_match('/^[a-zA-Z0-9_-]{11}$/', $youtubeVideoId)) {
            throw new RuntimeException('Ungültige YouTube-Video-ID.');
        }

        $client = $this->createAuthorizedClientForChannel($channelDbId);
        $youtube = new YouTube($client);
        $chunkSize = 256 * 1024;

        $client->setDefer(true);
        $request = $youtube->thumbnails->set($youtubeVideoId);

        $media = new MediaFileUpload(
            $client,
            $request,
            $mime,
            '',
            true,
            $chunkSize
        );
        $size = filesize($absolutePath);
        if ($size === false) {
            $client->setDefer(false);
            throw new RuntimeException('Dateigröße konnte nicht ermittelt werden.');
        }
        $media->setFileSize($size);

        $handle = fopen($absolutePath, 'rb');
        if ($handle === false) {
            $client->setDefer(false);
            throw new RuntimeException('Thumbnail konnte nicht geöffnet werden.');
        }
        try {
            while (!feof($handle)) {
                $chunk = fread($handle, $chunkSize);
                if ($chunk === false || $chunk === '') {
                    break;
                }
                $result = $media->nextChunk($chunk);
                if ($result !== false) {
                    break;
                }
            }
        } finally {
            fclose($handle);
            $client->setDefer(false);
        }
    }

    /**
     * Untertitel-Datei (z. B. .srt) hochladen — erfordert passende OAuth-Scopes.
     */
    public function uploadCaptionFromFile(
        int $channelDbId,
        string $youtubeVideoId,
        string $absolutePath,
        string $languageCode,
        string $trackName,
        bool $syncWithAudio
    ): void {
        if (!is_readable($absolutePath)) {
            throw new RuntimeException('Untertitel-Datei nicht lesbar.');
        }
        if (!preg_match('/^[a-zA-Z0-9_-]{11}$/', $youtubeVideoId)) {
            throw new RuntimeException('Ungültige YouTube-Video-ID.');
        }

        $client = $this->createAuthorizedClientForChannel($channelDbId);
        $youtube = new YouTube($client);

        $snippet = new CaptionSnippet();
        $snippet->setVideoId($youtubeVideoId);
        $snippet->setLanguage($languageCode);
        $snippet->setName($trackName !== '' ? $trackName : $languageCode);
        $snippet->setTrackKind(CaptionSnippet::TRACK_KIND_standard);

        $caption = new Caption();
        $caption->setSnippet($snippet);

        $chunkSize = 256 * 1024;
        $client->setDefer(true);
        $insert = $youtube->captions->insert('snippet', $caption, [
            'sync' => $syncWithAudio,
        ]);

        $media = new MediaFileUpload(
            $client,
            $insert,
            'application/octet-stream',
            '',
            true,
            $chunkSize
        );
        $size = filesize($absolutePath);
        if ($size === false) {
            $client->setDefer(false);
            throw new RuntimeException('Untertitel-Größe konnte nicht ermittelt werden.');
        }
        $media->setFileSize($size);

        $handle = fopen($absolutePath, 'rb');
        if ($handle === false) {
            $client->setDefer(false);
            throw new RuntimeException('Untertitel konnte nicht geöffnet werden.');
        }
        try {
            while (!feof($handle)) {
                $chunk = fread($handle, $chunkSize);
                if ($chunk === false || $chunk === '') {
                    break;
                }
                $result = $media->nextChunk($chunk);
                if ($result !== false) {
                    break;
                }
            }
        } finally {
            fclose($handle);
            $client->setDefer(false);
        }
    }

    /**
     * Zusätzliche Titels/Beschreibungen pro Sprache (YouTube „Übersetzungen“).
     *
     * @param array<string, array{title?: string, description?: string}> $localizations Locale (BCP-47) → Texte
     */
    public function applyVideoLocalizations(
        int $channelDbId,
        string $youtubeVideoId,
        array $localizations,
        ?string $skipLocaleSameAs = null
    ): void {
        if ($localizations === []) {
            return;
        }
        $client = $this->createAuthorizedClientForChannel($channelDbId);
        $youtube = new YouTube($client);

        $map = [];
        foreach ($localizations as $locale => $pair) {
            if (!LocaleTag::isLikely((string) $locale)) {
                continue;
            }
            $localeKey = (string) $locale;
            if ($skipLocaleSameAs !== null && strtolower($localeKey) === strtolower($skipLocaleSameAs)) {
                continue;
            }
            $title = trim((string) ($pair['title'] ?? ''));
            $desc = trim((string) ($pair['description'] ?? ''));
            if ($title === '' && $desc === '') {
                continue;
            }
            $vl = new VideoLocalization();
            if ($title !== '') {
                $vl->setTitle($title);
            }
            if ($desc !== '') {
                $vl->setDescription($desc);
            }
            $map[$localeKey] = $vl;
        }
        if ($map === []) {
            return;
        }

        $video = new Video();
        $video->setId($youtubeVideoId);
        $video->setLocalizations($map);
        $youtube->videos->update('localizations', $video, []);
    }

    /**
     * @param array<string, mixed> $options default_language, default_audio_language, tags, category_id, localizations (locale => [title, description])
     *
     * @return string YouTube-Video-ID
     */
    public function uploadLocalFile(
        int $channelDbId,
        string $absolutePath,
        string $title,
        string $description,
        string $privacyStatus,
        bool $notifySubscribers,
        array $options = []
    ): string {
        if (!is_readable($absolutePath)) {
            throw new RuntimeException('Videodatei nicht lesbar.');
        }
        $client = $this->createAuthorizedClientForChannel($channelDbId);

        $allowed = ['private', 'unlisted', 'public'];
        if (!in_array($privacyStatus, $allowed, true)) {
            $privacyStatus = 'private';
        }

        $snippet = new VideoSnippet();
        $snippet->setTitle($title);
        $snippet->setDescription($description);

        $defaultLang = isset($options['default_language']) ? trim((string) $options['default_language']) : '';
        if ($defaultLang !== '' && LocaleTag::isLikely($defaultLang)) {
            $snippet->setDefaultLanguage($defaultLang);
        }

        $defaultAudio = isset($options['default_audio_language']) ? trim((string) $options['default_audio_language']) : '';
        if ($defaultAudio !== '' && LocaleTag::isLikely($defaultAudio)) {
            $snippet->setDefaultAudioLanguage($defaultAudio);
        }

        if (!empty($options['tags']) && is_array($options['tags'])) {
            $tags = array_values(array_filter(array_map('strval', $options['tags']), static fn ($t) => $t !== ''));
            $tags = array_slice($tags, 0, 30);
            if ($tags !== []) {
                $snippet->setTags($tags);
            }
        }

        $catId = isset($options['category_id']) ? trim((string) $options['category_id']) : '';
        if ($catId !== '' && preg_match('/^\d+$/', $catId)) {
            $snippet->setCategoryId($catId);
        }

        $status = new VideoStatus();
        $status->setPrivacyStatus($privacyStatus);

        $video = new Video();
        $video->setSnippet($snippet);
        $video->setStatus($status);

        $youtube = new YouTube($client);
        $chunkSize = 1024 * 1024;

        $client->setDefer(true);
        $insert = $youtube->videos->insert('snippet,status', $video, [
            'notifySubscribers' => $notifySubscribers,
        ]);

        $media = new MediaFileUpload(
            $client,
            $insert,
            'video/*',
            '',
            true,
            $chunkSize
        );
        $size = filesize($absolutePath);
        if ($size === false) {
            $client->setDefer(false);
            throw new RuntimeException('Dateigröße konnte nicht ermittelt werden.');
        }
        $media->setFileSize($size);

        $handle = fopen($absolutePath, 'rb');
        if ($handle === false) {
            $client->setDefer(false);
            throw new RuntimeException('Datei konnte nicht geöffnet werden.');
        }
        $result = false;
        try {
            while (!feof($handle)) {
                $chunk = fread($handle, $chunkSize);
                if ($chunk === false || $chunk === '') {
                    break;
                }
                $result = $media->nextChunk($chunk);
                if ($result !== false) {
                    break;
                }
            }
        } finally {
            fclose($handle);
            $client->setDefer(false);
        }

        if (!($result instanceof Video)) {
            throw new RuntimeException('Upload unvollständig oder keine Video-ID erhalten (Quota/Scopes prüfen).');
        }
        $id = $result->getId();
        if ($id === '') {
            throw new RuntimeException('Upload unvollständig oder keine Video-ID erhalten (Quota/Scopes prüfen).');
        }

        $locs = $options['localizations'] ?? [];
        if (is_array($locs) && $locs !== []) {
            $this->applyVideoLocalizations($channelDbId, $id, $locs, $defaultLang !== '' ? $defaultLang : null);
        }

        return $id;
    }
}
