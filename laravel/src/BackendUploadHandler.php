<?php

declare(strict_types=1);

namespace YtHub;

use PDO;
use RuntimeException;

/**
 * Verarbeitet Video- und Thumbnail-Uploads für public/backend.php (interner Token).
 */
final class BackendUploadHandler
{
    /**
     * @param callable(string $key): string $t Übersetzungsfunktion (z. B. Lang::t)
     * @return array{ok: bool, message: string, videoId: ?string}
     */
    public static function process(
        PDO $pdo,
        TokenCipher $cipher,
        string $csrfSession,
        string $csrfPosted,
        callable $t
    ): array {
        if ($csrfSession === '' || !hash_equals($csrfSession, $csrfPosted)) {
            return ['ok' => false, 'message' => $t('backend.upload_error_csrf'), 'videoId' => null];
        }

        $channelId = (int) ($_POST['channel_id'] ?? 0);
        if ($channelId <= 0) {
            return ['ok' => false, 'message' => $t('backend.upload_error_channel'), 'videoId' => null];
        }

        $repo = new ChannelRepository($pdo);
        $channels = $repo->listActiveForBackend();
        $allowed = [];
        foreach ($channels as $c) {
            if (!empty($c['refresh_token'])) {
                $allowed[(int) $c['id']] = true;
            }
        }
        if (!isset($allowed[$channelId])) {
            return ['ok' => false, 'message' => $t('backend.upload_error_channel'), 'videoId' => null];
        }

        $youtubeVideoId = trim((string) ($_POST['youtube_video_id'] ?? ''));
        $hasVideoFile = isset($_FILES['video']) && is_array($_FILES['video'])
            && (int) ($_FILES['video']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK;
        $hasThumbFile = isset($_FILES['thumbnail']) && is_array($_FILES['thumbnail'])
            && (int) ($_FILES['thumbnail']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK;

        $thumbOnly = !$hasVideoFile && $youtubeVideoId !== '' && $hasThumbFile;

        if ($thumbOnly) {
            $tmpT = (string) ($_FILES['thumbnail']['tmp_name'] ?? '');
            if ($tmpT === '' || !is_uploaded_file($tmpT)) {
                return ['ok' => false, 'message' => $t('backend.upload_error_thumb'), 'videoId' => null];
            }
            $destT = sys_get_temp_dir() . '/' . uniqid('bu_thumb_', true);
            $ext = strtolower(pathinfo((string) ($_FILES['thumbnail']['name'] ?? ''), PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png'], true)) {
                $destT .= '.' . $ext;
            } else {
                $destT .= '.jpg';
            }
            if (!@move_uploaded_file($tmpT, $destT)) {
                return ['ok' => false, 'message' => $t('backend.upload_error_move'), 'videoId' => null];
            }
            try {
                $svc = new YouTubeUploadService($pdo, $cipher);
                $svc->setThumbnailFromFile($channelId, $youtubeVideoId, $destT);
            } catch (\Throwable $e) {
                @unlink($destT);

                return ['ok' => false, 'message' => $e->getMessage(), 'videoId' => null];
            }
            @unlink($destT);

            return [
                'ok' => true,
                'message' => sprintf($t('backend.upload_ok_thumb'), $youtubeVideoId),
                'videoId' => $youtubeVideoId,
            ];
        }

        if (!$hasVideoFile) {
            return ['ok' => false, 'message' => $t('backend.upload_error_video'), 'videoId' => null];
        }

        $title = trim((string) ($_POST['title'] ?? ''));
        if ($title === '') {
            return ['ok' => false, 'message' => $t('backend.upload_error_title'), 'videoId' => null];
        }

        $description = (string) ($_POST['description'] ?? '');
        $privacy = (string) ($_POST['privacy'] ?? 'private');
        $notify = isset($_POST['notify_subscribers']);

        $uploadOptions = YouTubeUploadForm::buildUploadOptionsFromPost($_POST);
        $captionPrepared = null;
        try {
            $captionPrepared = YouTubeUploadForm::prepareCaptionFromRequest($_POST, $_FILES);
        } catch (RuntimeException $e) {
            $code = $e->getMessage();
            if ($code === 'caption_ext') {
                return ['ok' => false, 'message' => $t('backend.upload_error_caption_ext'), 'videoId' => null];
            }
            if ($code === 'caption_move') {
                return ['ok' => false, 'message' => $t('backend.upload_error_move'), 'videoId' => null];
            }
            if ($code === 'caption_lang') {
                return ['ok' => false, 'message' => $t('backend.upload_error_caption_lang'), 'videoId' => null];
            }

            return ['ok' => false, 'message' => $e->getMessage(), 'videoId' => null];
        }

        $tmpV = (string) ($_FILES['video']['tmp_name'] ?? '');
        if ($tmpV === '' || !is_uploaded_file($tmpV)) {
            if ($captionPrepared !== null) {
                @unlink($captionPrepared['path']);
            }

            return ['ok' => false, 'message' => $t('backend.upload_error_move'), 'videoId' => null];
        }
        $destV = sys_get_temp_dir() . '/' . uniqid('bu_vid_', true) . '.bin';
        if (!@move_uploaded_file($tmpV, $destV)) {
            if ($captionPrepared !== null) {
                @unlink($captionPrepared['path']);
            }

            return ['ok' => false, 'message' => $t('backend.upload_error_move'), 'videoId' => null];
        }

        $thumbOk = false;
        $captionNote = '';
        try {
            $svc = new YouTubeUploadService($pdo, $cipher);
            $vid = $svc->uploadLocalFile($channelId, $destV, $title, $description, $privacy, $notify, $uploadOptions);
            if ($hasThumbFile) {
                $tmpT2 = (string) ($_FILES['thumbnail']['tmp_name'] ?? '');
                if ($tmpT2 !== '' && is_uploaded_file($tmpT2)) {
                    $destT2 = sys_get_temp_dir() . '/' . uniqid('bu_thumb_', true);
                    $ext2 = strtolower(pathinfo((string) ($_FILES['thumbnail']['name'] ?? ''), PATHINFO_EXTENSION));
                    $destT2 .= in_array($ext2, ['jpg', 'jpeg', 'png'], true) ? '.' . $ext2 : '.jpg';
                    if (@move_uploaded_file($tmpT2, $destT2)) {
                        try {
                            $svc->setThumbnailFromFile($channelId, $vid, $destT2);
                            $thumbOk = true;
                        } finally {
                            @unlink($destT2);
                        }
                    }
                }
            }
            if ($captionPrepared !== null) {
                try {
                    $svc->uploadCaptionFromFile(
                        $channelId,
                        $vid,
                        $captionPrepared['path'],
                        $captionPrepared['language'],
                        $captionPrepared['name'],
                        $captionPrepared['sync']
                    );
                    $captionNote = ' ' . $t('yt.caption_ok');
                } catch (\Throwable $e) {
                    $captionNote = ' ' . sprintf($t('yt.caption_warn'), $e->getMessage());
                } finally {
                    @unlink($captionPrepared['path']);
                }
            }
        } catch (\Throwable $e) {
            @unlink($destV);
            if ($captionPrepared !== null) {
                @unlink($captionPrepared['path']);
            }

            return ['ok' => false, 'message' => $e->getMessage(), 'videoId' => null];
        }
        @unlink($destV);

        $msg = $thumbOk
            ? sprintf($t('backend.upload_ok_both'), $vid)
            : sprintf($t('backend.upload_ok_video'), $vid);
        $msg .= $captionNote;

        return ['ok' => true, 'message' => $msg, 'videoId' => $vid];
    }
}
