<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use YtHub\AdminFlash;
use YtHub\ChannelRepository;
use YtHub\Csrf;
use YtHub\Db;
use YtHub\Lang;
use YtHub\PublicHttp;
use YtHub\StaffRepository;

final class StaffChannelsController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        require_once base_path('src/bootstrap.php');

        Lang::init();

        $pdo = Db::pdo();
        $repo = new StaffRepository($pdo);
        $channelRepo = new ChannelRepository($pdo);

        $id = (int) $request->query('id', 0);
        $staff = $id > 0 ? $repo->findById($id) : null;
        if ($staff === null) {
            abort(404, Lang::t('admin.staff_not_found'));
        }

        $allChannels = $channelRepo->listAllAdmin();
        $states = $repo->getChannelAccessStates($id);

        if ($request->isMethod('post')) {
            PublicHttp::sendSecurityHeaders();

            if (! Csrf::validate($request->input('csrf_token'))) {
                AdminFlash::error(Lang::t('admin.staff_flash_csrf'));
            } else {
                $map = [];
                /** @var array<int|string, string> $postCh */
                $postCh = $request->input('ch', []);
                if (! is_array($postCh)) {
                    $postCh = [];
                }
                foreach ($allChannels as $c) {
                    $cid = (int) $c['id'];
                    $raw = (string) ($postCh[$cid] ?? 'none');
                    $map[$cid] = in_array($raw, ['none', 'allow', 'block'], true) ? $raw : 'none';
                }
                $repo->setChannelAccessMap($id, $map);
                AdminFlash::success(Lang::t('admin.staff_channels_saved'));
            }

            return redirect('/admin/staff_channels.php?id='.$id);
        }

        PublicHttp::sendSecurityHeaders();

        return view('admin.staff_channels', [
            'id' => $id,
            'staff' => $staff,
            'allChannels' => $allChannels,
            'states' => $states,
        ]);
    }
}
