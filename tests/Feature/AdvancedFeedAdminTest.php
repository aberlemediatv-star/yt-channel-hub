<?php

namespace Tests\Feature;

use App\Models\AdvancedFeed;
use App\Models\AdvancedFeedItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AdvancedFeedAdminTest extends TestCase
{
    use RefreshDatabase;

    private function token(): string
    {
        return (string) config('app.internal_token');
    }

    public function test_index_requires_internal_token(): void
    {
        $this->get('/admin/advanced-feeds')->assertForbidden();
    }

    public function test_store_creates_feed(): void
    {
        $this->post('/admin/advanced-feeds?token='.urlencode($this->token()), [
            'title' => 'My FAST Feed',
            'channel_id' => 1,
            'language' => 'de',
            'tmdb_enabled' => '1',
            'is_active' => '1',
        ])->assertRedirect();

        $feed = AdvancedFeed::query()->where('title', 'My FAST Feed')->first();
        $this->assertNotNull($feed);
        $this->assertTrue($feed->tmdb_enabled);
        $this->assertTrue($feed->is_active);
        $this->assertSame('de', $feed->language);
    }

    public function test_add_and_remove_item(): void
    {
        $feed = AdvancedFeed::query()->create([
            'slug' => 'test-feed',
            'title' => 'Test',
            'channel_id' => 1,
            'language' => 'en',
            'tmdb_enabled' => false,
            'is_active' => true,
        ]);

        $this->post('/admin/advanced-feeds/'.$feed->id.'/items?token='.urlencode($this->token()), [
            'youtube_video_id' => 'dQw4w9WgXcQ',
        ])->assertRedirect();

        $item = AdvancedFeedItem::query()->where('advanced_feed_id', $feed->id)->first();
        $this->assertNotNull($item);
        $this->assertSame('dQw4w9WgXcQ', $item->youtube_video_id);

        $this->delete('/admin/advanced-feeds/'.$feed->id.'/items/'.$item->id.'/remove?token='.urlencode($this->token()))
            ->assertRedirect();

        $this->assertFalse(AdvancedFeedItem::query()->whereKey($item->id)->exists());
    }

    public function test_multiple_feeds_per_channel(): void
    {
        $t = $this->token();

        $this->post('/admin/advanced-feeds?token='.urlencode($t), [
            'title' => 'Kanal 1 DE',
            'channel_id' => 1,
            'language' => 'de',
            'is_active' => '1',
        ])->assertRedirect();

        $this->post('/admin/advanced-feeds?token='.urlencode($t), [
            'title' => 'Kanal 1 EN',
            'channel_id' => 1,
            'language' => 'en',
            'is_active' => '1',
        ])->assertRedirect();

        $this->post('/admin/advanced-feeds?token='.urlencode($t), [
            'title' => 'Kanal 1 Highlights',
            'channel_id' => 1,
            'language' => 'de',
            'is_active' => '1',
        ])->assertRedirect();

        $feeds = AdvancedFeed::query()->where('channel_id', 1)->get();
        $this->assertCount(3, $feeds);

        $slugs = $feeds->pluck('slug')->toArray();
        $this->assertCount(3, array_unique($slugs));
    }

    public function test_effective_title_priority(): void
    {
        $item = new AdvancedFeedItem;
        $item->tmdb_title = 'TMDB Title';
        $item->custom_title = null;
        $this->assertSame('TMDB Title', $item->effectiveTitle());

        $item->custom_title = 'Custom Override';
        $this->assertSame('Custom Override', $item->effectiveTitle());

        $item->custom_title = null;
        $item->tmdb_title = null;
        $this->assertNull($item->effectiveTitle());
    }
}
