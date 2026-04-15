<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Site\Concerns\BootstrapsYtHubPublic;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use YtHub\ChannelRepository;
use YtHub\Db;
use YtHub\Lang;
use YtHub\VideoRepository;

final class HomeController extends Controller
{
    use BootstrapsYtHubPublic;

    public function index(): View
    {
        $this->bootstrapYtHubPublic();

        $pdo = Db::pdo();
        $channels = (new ChannelRepository($pdo))->listActiveForFrontend();
        $ttl = (int) config('ythub.public_home_cache_ttl', 0);
        $cacheKey = $this->publicHomeCacheKey($channels);
        $payload = $ttl > 0
            ? Cache::remember($cacheKey, $ttl, fn (): array => $this->buildPublicHomePayload($channels))
            : $this->buildPublicHomePayload($channels);

        return view('site.home', [
            'byChannel' => $payload['byChannel'],
            'seoTitle' => Lang::t('public.title').' — '.Lang::t('public.brand'),
            'seoDescription' => Lang::t('public.meta_description'),
            'seoCanonicalPath' => 'index.php',
            'seoIncludeJsonLd' => true,
            'hreflangPage' => 'index.php',
            'seoOgImage' => $payload['seoOgImage'],
        ]);
    }

    /**
     * @param  list<array<string, mixed>>  $channels
     */
    private function publicHomeCacheKey(array $channels): string
    {
        $sig = implode(',', array_map(static function (array $c): string {
            return ((int) ($c['id'] ?? 0)).'|'.((int) ($c['sort_order'] ?? 0));
        }, $channels));

        return 'yt_hub:public_home:v1:'.Lang::code().':'.hash('xxh128', $sig);
    }

    /**
     * @param  list<array<string, mixed>>  $channels
     * @return array{byChannel: list<array{channel: array<string, mixed>, videos: list<array<string, mixed>>}>, seoOgImage: ?string}
     */
    private function buildPublicHomePayload(array $channels): array
    {
        $pdo = Db::pdo();
        $videos = new VideoRepository($pdo);

        $byChannel = [];
        $seoOgImage = null;
        foreach ($channels as $ch) {
            $list = $videos->latestByChannel((int) $ch['id'], 48);
            $byChannel[] = [
                'channel' => $ch,
                'videos' => $list,
            ];
            if ($seoOgImage === null) {
                foreach ($list as $row) {
                    $u = trim((string) ($row['thumbnail_url'] ?? ''));
                    if ($u !== '') {
                        $seoOgImage = $u;
                        break;
                    }
                }
            }
        }

        return ['byChannel' => $byChannel, 'seoOgImage' => $seoOgImage];
    }
}
