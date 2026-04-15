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

final class ChannelController extends Controller
{
    public function edit(Request $request): View
    {
        require_once base_path('src/bootstrap.php');

        PublicHttp::sendSecurityHeaders();
        Lang::init();

        $id = (int) $request->query('id', 0);
        $row = null;
        if ($id > 0) {
            $row = (new ChannelRepository(Db::pdo()))->findById($id);
            if (! $row) {
                abort(404, Lang::t('admin.not_found'));
            }
        }

        return view('admin.channel_edit', [
            'id' => $id,
            'row' => $row ?? [],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        require_once base_path('src/bootstrap.php');

        if (! $request->isMethod('post')) {
            abort(405);
        }

        if (! Csrf::validate($request->input('csrf_token'))) {
            AdminFlash::error('Ungültige Anfrage (CSRF). Bitte Seite neu laden.');

            return redirect('/admin/index.php');
        }

        $slug = trim((string) $request->input('slug', ''));
        $title = trim((string) $request->input('title', ''));
        $yt = trim((string) $request->input('youtube_channel_id', ''));
        $sort = (int) $request->input('sort_order', 0);
        $active = $request->has('is_active');
        $id = (int) $request->input('id', 0);

        if ($slug === '' || $title === '' || $yt === '' || ! preg_match('/^[a-z0-9\-]{1,64}$/', $slug)) {
            AdminFlash::error('Slug: nur Kleinbuchstaben, Zahlen, Bindestrich (1–64 Zeichen).');

            return redirect($id > 0 ? '/admin/channel_edit.php?id='.$id : '/admin/channel_edit.php');
        }

        $repo = new ChannelRepository(Db::pdo());
        try {
            if ($id > 0) {
                $repo->update($id, $slug, $title, $yt, $sort, $active);
                AdminFlash::success('Kanal gespeichert.');
            } else {
                $repo->insert($slug, $title, $yt, $sort, $active);
                AdminFlash::success('Kanal angelegt.');
            }
        } catch (\PDOException $e) {
            if ($e->getCode() === '23000' || str_contains($e->getMessage(), 'Duplicate')) {
                AdminFlash::error('Slug oder YouTube-ID ist bereits vergeben.');
            } else {
                AdminFlash::error('Datenbankfehler beim Speichern.');
            }

            return redirect($id > 0 ? '/admin/channel_edit.php?id='.$id : '/admin/channel_edit.php');
        }

        return redirect('/admin/index.php');
    }

    public function destroy(Request $request): RedirectResponse
    {
        require_once base_path('src/bootstrap.php');

        if (! $request->isMethod('post')) {
            abort(405);
        }

        if (! Csrf::validate($request->input('csrf_token'))) {
            AdminFlash::error('Ungültige Anfrage (CSRF). Bitte Seite neu laden.');

            return redirect('/admin/index.php');
        }

        $id = (int) $request->input('id', 0);
        if ($id <= 0) {
            AdminFlash::error('Ungültige Kanal-ID.');

            return redirect('/admin/index.php');
        }

        (new ChannelRepository(Db::pdo()))->delete($id);
        AdminFlash::success('Kanal gelöscht.');

        return redirect('/admin/index.php');
    }
}
