<?php

namespace Tests\Feature;

use App\Models\SocialAccount;
use App\Models\SocialPost;
use App\Models\SocialSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class SocialPublishToXTest extends TestCase
{
    use RefreshDatabase;

    public function test_enqueue_x_post_persists_published_when_api_ok(): void
    {
        Http::fake([
            'https://api.x.com/2/tweets' => Http::response(['data' => ['id' => 'tweet-abc']], 201),
        ]);

        SocialAccount::query()->create([
            'platform' => 'x',
            'label' => 'Test',
            'access_token' => 'test-access-token',
            'refresh_token' => null,
            'token_expires_at' => null,
            'scopes' => 'tweet.write',
            'meta' => [],
        ]);

        $token = (string) config('app.internal_token');
        $this->post('/admin/social/posts/x?token='.urlencode($token), [
            'text' => 'Hello from test',
        ])->assertRedirect();

        $post = SocialPost::query()->where('platform', 'x')->first();
        $this->assertNotNull($post);
        $this->assertSame('published', $post->status);
        $this->assertSame('tweet-abc', $post->external_id);
    }

    public function test_enqueue_x_marks_failed_when_api_errors(): void
    {
        Http::fake([
            'https://api.x.com/2/tweets' => Http::response(['detail' => 'invalid'], 403),
        ]);

        SocialAccount::query()->create([
            'platform' => 'x',
            'label' => 'Test',
            'access_token' => 'bad-token',
            'refresh_token' => null,
            'token_expires_at' => null,
            'scopes' => '',
            'meta' => [],
        ]);

        $token = (string) config('app.internal_token');
        $this->post('/admin/social/posts/x?token='.urlencode($token), [
            'text' => 'x',
        ])->assertRedirect();

        $post = SocialPost::query()->where('platform', 'x')->first();
        $this->assertNotNull($post);
        $this->assertSame('failed', $post->status);
        $this->assertStringContainsString('X API Fehler', (string) $post->error_message);
    }

    public function test_enqueue_x_refreshes_token_when_expired_before_tweet(): void
    {
        SocialSetting::setEncrypted('x.client_id', 'cid');
        SocialSetting::setEncrypted('x.client_secret', 'secret');

        Http::fake(function (Request $request) {
            $url = $request->url();
            if (str_contains($url, 'oauth2/token')) {
                return Http::response([
                    'access_token' => 'fresh-access',
                    'refresh_token' => 'fresh-refresh',
                    'expires_in' => 7200,
                    'scope' => 'tweet.write',
                ], 200);
            }
            if (str_contains($url, '2/tweets')) {
                return Http::response(['data' => ['id' => 'after-refresh']], 201);
            }

            return Http::response('unexpected', 500);
        });

        SocialAccount::query()->create([
            'platform' => 'x',
            'label' => 'Test',
            'access_token' => 'stale-access',
            'refresh_token' => 'old-refresh',
            'token_expires_at' => now()->subHour(),
            'scopes' => 'tweet.write',
            'meta' => [],
        ]);

        $token = (string) config('app.internal_token');
        $this->post('/admin/social/posts/x?token='.urlencode($token), [
            'text' => 'After refresh',
        ])->assertRedirect();

        $acct = SocialAccount::query()->where('platform', 'x')->first();
        $this->assertNotNull($acct);
        $this->assertSame('fresh-access', $acct->access_token);

        $post = SocialPost::query()->where('platform', 'x')->first();
        $this->assertNotNull($post);
        $this->assertSame('published', $post->status);
        $this->assertSame('after-refresh', $post->external_id);
    }
}
