<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SocialAccount;
use App\Models\SocialSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use YtHub\Lang;

final class SocialAccountsController extends Controller
{
    public function index(Request $request): View
    {
        Lang::init();

        return view('admin.social.accounts', [
            'token' => (string) $request->query('token', ''),
            'accounts' => SocialAccount::query()->orderByDesc('id')->limit(200)->get(),
            'xOAuthReady' => SocialSetting::getDecrypted('x.client_id', '') !== ''
                && SocialSetting::getDecrypted('x.client_secret', '') !== '',
            'tiktokOAuthReady' => SocialSetting::getDecrypted('tiktok.client_key', '') !== ''
                && SocialSetting::getDecrypted('tiktok.client_secret', '') !== '',
        ]);
    }

    public function disconnect(Request $request, SocialAccount $account): RedirectResponse
    {
        $account->delete();

        return redirect()
            ->to('/admin/social/accounts?token='.urlencode((string) $request->query('token', '')))
            ->with('status', 'Account entfernt.');
    }
}
