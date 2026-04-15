<?php

namespace Tests\Feature;

use App\Models\SocialAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SocialAccountsDisconnectTest extends TestCase
{
    use RefreshDatabase;

    public function test_disconnect_requires_internal_token(): void
    {
        $acct = SocialAccount::query()->create([
            'platform' => 'x',
            'label' => 'x',
            'access_token' => 't',
            'refresh_token' => null,
            'token_expires_at' => null,
            'scopes' => '',
            'meta' => [],
        ]);

        $this->post('/admin/social/accounts/'.$acct->id.'/disconnect')->assertForbidden();
        $this->assertTrue(SocialAccount::query()->whereKey($acct->id)->exists());
    }

    public function test_disconnect_removes_account(): void
    {
        $acct = SocialAccount::query()->create([
            'platform' => 'x',
            'label' => 'x',
            'access_token' => 't',
            'refresh_token' => null,
            'token_expires_at' => null,
            'scopes' => '',
            'meta' => [],
        ]);

        $token = (string) config('app.internal_token');
        $this->post('/admin/social/accounts/'.$acct->id.'/disconnect?token='.urlencode($token))
            ->assertRedirect();

        $this->assertFalse(SocialAccount::query()->whereKey($acct->id)->exists());
    }
}
