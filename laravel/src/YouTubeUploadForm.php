<?php

declare(strict_types=1);

namespace YtHub;

use RuntimeException;

/**
 * POST-Felder für erweiterte YouTube-Metadaten (Upload-Formulare Backend / Staff).
 */
final class YouTubeUploadForm
{
    /**
     * Optionen für {@see YouTubeUploadService::uploadLocalFile} $options.
     *
     * @return array<string, mixed>
     */
    public static function buildUploadOptionsFromPost(array $post): array
    {
        $opts = [];

        $dl = trim((string) ($post['meta_default_language'] ?? ''));
        if ($dl !== '' && LocaleTag::isLikely($dl)) {
            $opts['default_language'] = $dl;
        }

        $dal = trim((string) ($post['meta_default_audio_language'] ?? ''));
        if ($dal !== '' && LocaleTag::isLikely($dal)) {
            $opts['default_audio_language'] = $dal;
        }

        $tagsRaw = trim((string) ($post['meta_tags'] ?? ''));
        if ($tagsRaw !== '') {
            $tags = array_values(array_filter(array_map('trim', explode(',', $tagsRaw)), static fn ($x) => $x !== ''));
            $opts['tags'] = array_slice($tags, 0, 30);
        }

        $cat = trim((string) ($post['meta_category_id'] ?? ''));
        if ($cat !== '' && preg_match('/^\d+$/', $cat)) {
            $opts['category_id'] = $cat;
        }

        $locales = [];
        $locArr = $post['meta_loc_locale'] ?? null;
        if (is_array($locArr)) {
            $n = count($locArr);
            for ($i = 0; $i < $n; $i++) {
                $lc = trim((string) ($locArr[$i] ?? ''));
                if ($lc === '' || !LocaleTag::isLikely($lc)) {
                    continue;
                }
                $locales[$lc] = [
                    'title' => trim((string) (is_array($post['meta_loc_title'] ?? null) ? ($post['meta_loc_title'][$i] ?? '') : '')),
                    'description' => trim((string) (is_array($post['meta_loc_desc'] ?? null) ? ($post['meta_loc_desc'][$i] ?? '') : '')),
                ];
            }
        }
        if ($locales !== []) {
            $opts['localizations'] = $locales;
        }

        return $opts;
    }

    /**
     * Untertitel-Datei in ein Tempfile legen (oder null).
     *
     * @return array{path: string, language: string, name: string, sync: bool}|null
     */
    public static function prepareCaptionFromRequest(array $post, array $files): ?array
    {
        if (!isset($files['caption']) || !is_array($files['caption'])) {
            return null;
        }
        if ((int) ($files['caption']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return null;
        }
        $tmp = (string) ($files['caption']['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            return null;
        }
        $orig = (string) ($files['caption']['name'] ?? '');
        $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
        $allowed = ['srt', 'sbv', 'sub', 'vtt'];
        if (!in_array($ext, $allowed, true)) {
            throw new RuntimeException('caption_ext');
        }
        $dest = sys_get_temp_dir() . '/' . uniqid('yt_cap_', true) . '.' . $ext;
        if (!@move_uploaded_file($tmp, $dest)) {
            throw new RuntimeException('caption_move');
        }

        $lang = trim((string) ($post['meta_caption_language'] ?? ''));
        if ($lang === '' || !LocaleTag::isLikely($lang)) {
            @unlink($dest);
            throw new RuntimeException('caption_lang');
        }
        $name = trim((string) ($post['meta_caption_name'] ?? ''));
        $sync = isset($post['meta_caption_sync']);

        return [
            'path' => $dest,
            'language' => $lang,
            'name' => $name,
            'sync' => $sync,
        ];
    }
}
