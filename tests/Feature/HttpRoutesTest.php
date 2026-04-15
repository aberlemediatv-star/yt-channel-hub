<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Smoke-Tests für Routen ohne DB-Schreibzugriff (YtHub bootstrap + config/hub.php nötig).
 */
final class HttpRoutesTest extends TestCase
{
    public function test_health_endpoint_liveness_returns_json(): void
    {
        $response = $this->get('/health.php');

        $response->assertOk();
        $response->assertJson([
            'ok' => true,
            'scope' => 'liveness',
        ]);
    }

    public function test_datenschutz_returns_successful_html(): void
    {
        $response = $this->get('/datenschutz.php');

        $response->assertOk();
        $response->assertHeader('content-type', 'text/html; charset=UTF-8');
    }

    public function test_impressum_returns_successful_html(): void
    {
        $response = $this->get('/impressum.php');

        $response->assertOk();
        $response->assertHeader('content-type', 'text/html; charset=UTF-8');
    }

    public function test_staff_upload_loc_js_is_served(): void
    {
        $response = $this->get('/staff/upload-loc.js');

        $response->assertOk();
        $this->assertStringContainsString('javascript', strtolower($response->headers->get('content-type', '')));
    }

    public function test_admin_login_page_loads(): void
    {
        $response = $this->get('/admin/login.php');

        $response->assertOk();
        $response->assertSee('adm-login', false);
    }

    public function test_admin_api_keys_requires_internal_token(): void
    {
        $this->get('/admin/api-keys')->assertForbidden();
    }

    public function test_oauth_x_start_requires_internal_token(): void
    {
        $this->get('/oauth/x/start')->assertForbidden();
        $this->get('/oauth/x/start?token=wrong')->assertForbidden();
    }

    public function test_oauth_x_callback_without_session_returns_bad_request(): void
    {
        $this->get('/oauth/x/callback')->assertStatus(400);
    }

    public function test_staff_cloud_files_redirects_to_login_when_unauthenticated(): void
    {
        $this->get('/staff/cloud/files?channel_id=1&provider=gdrive')
            ->assertRedirect('/staff/login.php');
    }

    public function test_staff_videos_redirects_to_login_when_unauthenticated(): void
    {
        $this->get('/staff/videos.php')
            ->assertRedirect('/staff/login.php');
    }
}
