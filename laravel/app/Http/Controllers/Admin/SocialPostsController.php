<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\PublishToXJob;
use App\Models\SocialPost;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use YtHub\Lang;

final class SocialPostsController extends Controller
{
    public function index(Request $request): View
    {
        Lang::init();

        return view('admin.social.posts', [
            'token' => (string) $request->query('token', ''),
            'posts' => SocialPost::query()->orderByDesc('id')->limit(200)->get(),
        ]);
    }

    public function enqueueX(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'text' => ['nullable', 'string', 'max:250'],
            'youtube_video_id' => ['nullable', 'string', 'max:64'],
        ]);

        $text = trim((string) ($data['text'] ?? ''));
        $vid = trim((string) ($data['youtube_video_id'] ?? ''));
        if ($text === '' && $vid === '') {
            return redirect()
                ->to('/admin/social/posts?token='.urlencode((string) $request->query('token', '')))
                ->withErrors(['text' => 'Text und/oder YouTube-Video-ID angeben.']);
        }

        $post = SocialPost::query()->create([
            'platform' => 'x',
            'youtube_video_id' => $vid !== '' ? $vid : null,
            'local_video_path' => null,
            'status' => 'queued',
            'payload' => $text !== '' ? ['text' => $text] : null,
        ]);

        PublishToXJob::dispatch($post->id);

        return redirect()
            ->to('/admin/social/posts?token='.urlencode((string) $request->query('token', '')))
            ->with('status', 'X-Publish Job angestoßen — Status in der Tabelle prüfen.');
    }
}
