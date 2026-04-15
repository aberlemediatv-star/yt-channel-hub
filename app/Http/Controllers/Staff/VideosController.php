<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use YtHub\Db;
use YtHub\Lang;
use YtHub\PublicHttp;
use YtHub\StaffAuth;
use YtHub\StaffCsrf;
use YtHub\StaffModule;
use YtHub\StaffRepository;
use YtHub\TokenCipher;
use YtHub\VideoRepository;
use YtHub\YouTubeVideoBulkEditService;

final class VideosController extends Controller
{
    public function index(Request $request): View|Response|RedirectResponse
    {
        require_once base_path('src/bootstrap.php');

        PublicHttp::sendSecurityHeaders();
        Lang::init();

        $pdo = Db::pdo();
        $repo = new StaffRepository($pdo);
        $sid = StaffAuth::staffId();

        if (! $repo->hasModule($sid, StaffModule::EDIT_VIDEO)) {
            return response(Lang::t('staff.module_denied'), 403, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $mods = $repo->getMergedModules($sid);
        $allowedIds = $repo->allowedChannelIds($sid);
        $videos = $allowedIds === [] ? [] : (new VideoRepository($pdo))->recentForChannels($allowedIds, 150);

        if ($request->isMethod('post')) {
            if (! StaffCsrf::validate($request->input('csrf_token'))) {
                return redirect()->to('/staff/videos.php')
                    ->with('bulk_error', Lang::t('staff.bulk_err_csrf'));
            }

            $ids = $request->input('video_ids', []);
            if (! is_array($ids)) {
                $ids = [];
            }
            $ids = array_values(array_unique(array_filter(array_map('strval', $ids))));
            $ids = array_values(array_filter($ids, static fn (string $id) => preg_match('/^[a-zA-Z0-9_-]{11}$/', $id)));
            if ($ids === []) {
                return redirect()->to('/staff/videos.php')
                    ->with('bulk_error', Lang::t('staff.bulk_err_no_videos'));
            }
            if (count($ids) > 40) {
                return redirect()->to('/staff/videos.php')
                    ->with('bulk_error', Lang::t('staff.bulk_err_too_many'));
            }

            $options = [
                'privacy' => (string) $request->input('privacy', ''),
                'category_id' => trim((string) $request->input('category_id', '')),
                'tags_mode' => (string) $request->input('tags_mode', ''),
                'tags_text' => (string) $request->input('tags_text', ''),
                'title_mode' => (string) $request->input('title_mode', ''),
                'title_prefix' => (string) $request->input('title_prefix', ''),
                'title_suffix' => (string) $request->input('title_suffix', ''),
                'title_find' => (string) $request->input('title_find', ''),
                'title_replace' => (string) $request->input('title_replace', ''),
                'desc_mode' => (string) $request->input('desc_mode', ''),
                'desc_text' => (string) $request->input('desc_text', ''),
                'license' => (string) $request->input('license', ''),
                'embeddable' => (string) $request->input('embeddable', ''),
                'made_for_kids' => (string) $request->input('made_for_kids', ''),
                'public_stats' => (string) $request->input('public_stats', ''),
            ];

            if (! $this->hasAnyBulkOperation($options)) {
                return redirect()->to('/staff/videos.php')
                    ->with('bulk_error', Lang::t('staff.bulk_err_no_ops'));
            }

            if ($options['privacy'] !== '' && ! in_array($options['privacy'], ['private', 'unlisted', 'public'], true)) {
                $options['privacy'] = '';
            }
            if ($options['tags_mode'] !== '' && ! in_array($options['tags_mode'], ['clear', 'append', 'replace'], true)) {
                $options['tags_mode'] = '';
            }
            if ($options['title_mode'] !== '' && ! in_array($options['title_mode'], ['prefix', 'suffix', 'find_replace'], true)) {
                $options['title_mode'] = '';
            }
            if ($options['desc_mode'] !== '' && ! in_array($options['desc_mode'], ['prepend', 'append'], true)) {
                $options['desc_mode'] = '';
            }
            if ($options['license'] !== '' && ! in_array($options['license'], ['youtube', 'creativeCommon'], true)) {
                $options['license'] = '';
            }
            foreach (['embeddable', 'made_for_kids', 'public_stats'] as $k) {
                if ($options[$k] !== '' && $options[$k] !== '0' && $options[$k] !== '1') {
                    $options[$k] = '';
                }
            }

            $rows = (new VideoRepository($pdo))->rowsForVideoIdsInChannels($ids, $allowedIds);
            $resolved = array_column($rows, 'video_id');
            sort($resolved);
            $wanted = $ids;
            sort($wanted);
            if ($resolved !== $wanted) {
                return redirect()->to('/staff/videos.php')
                    ->with('bulk_error', Lang::t('staff.bulk_err_not_allowed'));
            }

            $cipher = new TokenCipher(app_config()['security']['encryption_key'] ?? null);
            $svc = new YouTubeVideoBulkEditService($pdo, $cipher);
            $result = $svc->apply($rows, $options);

            return redirect()->to('/staff/videos.php')->with('bulk_result', $result);
        }

        return view('staff.videos', [
            'mods' => $mods,
            'videos' => $videos,
        ]);
    }

    /**
     * @param  array<string, string>  $options
     */
    private function hasAnyBulkOperation(array $options): bool
    {
        if (($options['privacy'] ?? '') !== '') {
            return true;
        }
        if (trim((string) ($options['category_id'] ?? '')) !== '') {
            return true;
        }
        if (($options['tags_mode'] ?? '') !== '') {
            return true;
        }
        if (($options['title_mode'] ?? '') !== '') {
            return true;
        }
        if (($options['desc_mode'] ?? '') !== '') {
            return true;
        }
        if (($options['license'] ?? '') !== '') {
            return true;
        }
        if (($options['embeddable'] ?? '') !== '') {
            return true;
        }
        if (($options['made_for_kids'] ?? '') !== '') {
            return true;
        }
        if (($options['public_stats'] ?? '') !== '') {
            return true;
        }

        return false;
    }
}
