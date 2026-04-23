<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Services\CloudImport\DropboxCloudService;
use App\Services\CloudImport\GdriveCloudService;
use App\Services\CloudImport\S3VideoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use RuntimeException;
use YtHub\ChannelRepository;
use YtHub\Db;
use YtHub\Lang;
use YtHub\PublicHttp;
use YtHub\StaffAuth;
use YtHub\StaffCsrf;
use YtHub\StaffModule;
use YtHub\StaffRepository;
use YtHub\TokenCipher;
use YtHub\YouTubeUploadForm;
use YtHub\YouTubeUploadService;

final class UploadController extends Controller
{
    public function show(
        Request $request,
        GdriveCloudService $gdrive,
        DropboxCloudService $dropbox,
        S3VideoService $s3,
    ): View|Response|RedirectResponse {
        require_once base_path('src/bootstrap.php');

        PublicHttp::sendSecurityHeaders();
        Lang::init();

        $pdo = Db::pdo();
        $staffRepo = new StaffRepository($pdo);
        $sid = StaffAuth::staffId();

        $channelId = (int) $request->query('channel_id', 0);
        if ($channelId <= 0 || ! $staffRepo->staffMayAccessChannel($sid, $channelId)) {
            return response(Lang::t('staff.upload_error_channel_access'), 403, ['Content-Type' => 'text/plain; charset=utf-8']);
        }
        if (! $staffRepo->hasModule($sid, StaffModule::UPLOAD)) {
            return response(Lang::t('staff.module_denied'), 403, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $mods = $staffRepo->getMergedModules($sid);

        $row = (new ChannelRepository($pdo))->findById($channelId);
        $channelTitle = $row ? (string) $row['title'] : '';

        $error = '';
        $ok = (string) session()->pull('ok', '');
        $maxUpload = ini_get('upload_max_filesize') ?: '?';

        if ($request->isMethod('post')) {
            if (! StaffCsrf::validate($request->input('csrf_token'))) {
                $error = Lang::t('staff.upload_error_csrf');
            } else {
                $postCid = (int) $request->input('channel_id', 0);
                if ($postCid !== $channelId || ! $staffRepo->staffMayAccessChannel($sid, $postCid)) {
                    $error = Lang::t('staff.upload_error_bad_channel');
                } elseif (! $staffRepo->hasModule($sid, StaffModule::UPLOAD)) {
                    $error = Lang::t('staff.module_denied');
                } else {
                    $title = trim((string) $request->input('title', ''));
                    $desc = (string) $request->input('description', '');
                    $privacy = (string) $request->input('privacy', 'private');
                    $notify = $request->has('notify_subscribers');
                    $videoSource = (string) $request->input('video_source', 'local');

                    if ($title === '') {
                        $error = Lang::t('staff.upload_error_title');
                    } else {
                        $videoPath = '';
                        $unlinkVideo = false;
                        $captionPrepared = null;
                        try {
                            if ($videoSource === 'local') {
                                if (! isset($_FILES['video']) || ! is_array($_FILES['video'])) {
                                    throw new RuntimeException('no_file');
                                }
                                if (($_FILES['video']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                                    throw new RuntimeException('upload_code:'.(string) (int) ($_FILES['video']['error'] ?? 0));
                                }
                                $tmp = (string) ($_FILES['video']['tmp_name'] ?? '');
                                if ($tmp === '' || ! is_uploaded_file($tmp)) {
                                    throw new RuntimeException('bad_tmp');
                                }
                                $videoPath = $tmp;
                            } elseif ($videoSource === 'gdrive') {
                                if (! $gdrive->isConnected()) {
                                    throw new RuntimeException('gdrive_not_connected');
                                }
                                $fid = trim((string) $request->input('gdrive_file_id', ''));
                                if ($fid === '') {
                                    throw new RuntimeException('gdrive_pick');
                                }
                                $videoPath = $gdrive->downloadToTempFile($fid);
                                $unlinkVideo = true;
                            } elseif ($videoSource === 'dropbox') {
                                if (! $dropbox->isConnected()) {
                                    throw new RuntimeException('dropbox_not_connected');
                                }
                                $path = trim((string) $request->input('dropbox_path', ''));
                                if ($path === '') {
                                    throw new RuntimeException('dropbox_pick');
                                }
                                $videoPath = $dropbox->downloadToTempFile($path);
                                $unlinkVideo = true;
                            } elseif ($videoSource === 's3') {
                                if (! $s3->isConfigured()) {
                                    throw new RuntimeException('s3_not_configured');
                                }
                                $key = trim((string) $request->input('s3_key', ''));
                                if ($key === '') {
                                    throw new RuntimeException('s3_pick');
                                }
                                $videoPath = $s3->downloadToTempFile($key);
                                $unlinkVideo = true;
                            } else {
                                throw new RuntimeException('bad_source');
                            }
                        } catch (\Throwable $e) {
                            $code = $e instanceof RuntimeException ? $e->getMessage() : 'cloud_fail';
                            $error = $this->mapVideoSourceError($code);
                        }

                        if ($error === '' && $videoPath !== '') {
                            try {
                                $post = $request->all();
                                $uploadOptions = YouTubeUploadForm::buildUploadOptionsFromPost($post);
                                try {
                                    $captionPrepared = YouTubeUploadForm::prepareCaptionFromRequest($post, $_FILES);
                                } catch (RuntimeException $e) {
                                    $code = $e->getMessage();
                                    if ($code === 'caption_ext') {
                                        $error = Lang::t('backend.upload_error_caption_ext');
                                    } elseif ($code === 'caption_move') {
                                        $error = Lang::t('backend.upload_error_move');
                                    } elseif ($code === 'caption_lang') {
                                        $error = Lang::t('backend.upload_error_caption_lang');
                                    } else {
                                        \Illuminate\Support\Facades\Log::warning('staff_caption_prepare_failed', ['code' => $code]);
                                        $error = Lang::t('staff.upload_error_generic');
                                    }
                                }
                                if ($error === '') {
                                    set_time_limit(0);
                                    try {
                                        $cipher = new TokenCipher(app_config()['security']['encryption_key'] ?? null);
                                        $svc = new YouTubeUploadService($pdo, $cipher);
                                        $vid = $svc->uploadLocalFile(
                                            $channelId,
                                            $videoPath,
                                            $title,
                                            $desc,
                                            $privacy,
                                            $notify,
                                            $uploadOptions
                                        );
                                        $captionNote = '';
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
                                                $captionNote = ' '.Lang::t('yt.caption_ok');
                                            } catch (\Throwable $e) {
                                                $captionNote = ' '.sprintf(Lang::t('yt.caption_warn'), $e->getMessage());
                                            } finally {
                                                @unlink($captionPrepared['path']);
                                            }
                                        }
                                        $ok = sprintf(Lang::t('staff.upload_ok'), (string) $vid).$captionNote;

                                        return redirect()
                                            ->to('/staff/upload.php?channel_id='.$channelId)
                                            ->with('ok', $ok);
                                    } catch (\Throwable $e) {
                                        if ($captionPrepared !== null) {
                                            @unlink($captionPrepared['path']);
                                        }
                                        \Illuminate\Support\Facades\Log::error('staff_upload_failed', ['error' => $e->getMessage()]);
                                        $error = Lang::t('staff.upload_error_generic');
                                    }
                                }
                            } finally {
                                if ($unlinkVideo && $videoPath !== '' && is_file($videoPath)) {
                                    @unlink($videoPath);
                                }
                            }
                        }
                    }
                }
            }
        }

        return view('staff.upload', [
            'mods' => $mods,
            'channelId' => $channelId,
            'channelTitle' => $channelTitle,
            'error' => $error,
            'ok' => $ok,
            'cloudError' => (string) session()->pull('cloud_error', ''),
            'cloudOk' => (string) session()->pull('cloud_ok', ''),
            'maxUpload' => $maxUpload,
            'post' => $request->all(),
            'idPrefix' => 'st',
            'gdriveOAuthOk' => $gdrive->isOAuthConfigured(),
            'gdriveConnected' => $gdrive->isConnected(),
            'dropboxOAuthOk' => $dropbox->isOAuthConfigured(),
            'dropboxConnected' => $dropbox->isConnected(),
            's3Ready' => $s3->isConfigured(),
        ]);
    }

    private function mapVideoSourceError(string $code): string
    {
        if (str_starts_with($code, 'upload_code:')) {
            $n = (int) substr($code, strlen('upload_code:'));

            return sprintf(Lang::t('staff.upload_error_code'), $n);
        }

        return match ($code) {
            'no_file' => Lang::t('staff.upload_error_no_file'),
            'bad_tmp' => Lang::t('staff.upload_error_tmp'),
            'gdrive_not_connected', 'gdrive_pick', 'gdrive_bad_id' => Lang::t('staff.cloud_error_gdrive_pick'),
            'dropbox_not_connected', 'dropbox_pick' => Lang::t('staff.cloud_error_dropbox_pick'),
            's3_not_configured', 's3_pick' => Lang::t('staff.cloud_error_s3_pick'),
            'bad_source' => Lang::t('staff.cloud_error_bad_source'),
            'gdrive_oauth_not_configured' => Lang::t('staff.cloud_gdrive_not_configured'),
            'dropbox_oauth_not_configured' => Lang::t('staff.cloud_dropbox_not_configured'),
            'cloud_fail' => Lang::t('staff.cloud_error_download'),
            'gdrive_not_folder' => Lang::t('staff.cloud_error_not_folder'),
            'gdrive_token_expired' => Lang::t('staff.cloud_error_gdrive_pick'),
            default => $code,
        };
    }
}
