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
            'scheduled_for' => ['nullable', 'date'],
        ]);

        $text = trim((string) ($data['text'] ?? ''));
        $vid = trim((string) ($data['youtube_video_id'] ?? ''));
        if ($text === '' && $vid === '') {
            return redirect()
                ->to('/admin/social/posts')
                ->withErrors(['text' => 'Text und/oder YouTube-Video-ID angeben.']);
        }

        $scheduledFor = null;
        if (! empty($data['scheduled_for'])) {
            try {
                $scheduledFor = new \DateTimeImmutable((string) $data['scheduled_for']);
            } catch (\Throwable) {
                $scheduledFor = null;
            }
        }

        $immediate = $scheduledFor === null || $scheduledFor->getTimestamp() <= time();
        $status = $immediate ? SocialPost::STATUS_QUEUED : SocialPost::STATUS_SCHEDULED;

        $post = SocialPost::query()->create([
            'platform' => 'x',
            'youtube_video_id' => $vid !== '' ? $vid : null,
            'local_video_path' => null,
            'status' => $status,
            'payload' => $text !== '' ? ['text' => $text] : null,
            'scheduled_for' => $scheduledFor ? $scheduledFor->format('Y-m-d H:i:s') : null,
            'max_attempts' => 5,
        ]);

        if ($immediate) {
            PublishToXJob::dispatch($post->id);
            $msg = 'X-Publish Job angestoßen — Status in der Tabelle prüfen.';
        } else {
            $msg = 'X-Post geplant für ' . $scheduledFor->format('Y-m-d H:i') . ' — Scheduler (social:run-due) nimmt ihn auf.';
        }

        return redirect()
            ->to('/admin/social/posts')
            ->with('status', $msg);
    }
}
