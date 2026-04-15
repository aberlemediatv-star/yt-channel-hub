<?php

declare(strict_types=1);

namespace App\Services\CloudImport;

use Aws\S3\S3Client;
use RuntimeException;

final class S3VideoService
{
    public function isConfigured(): bool
    {
        if (! (bool) config('cloud_import.s3.enabled', false)) {
            return false;
        }
        $b = (string) config('cloud_import.s3.bucket', '');
        $k = (string) config('cloud_import.s3.key', '');
        $s = (string) config('cloud_import.s3.secret', '');

        return $b !== '' && $k !== '' && $s !== '';
    }

    /**
     * @return list<array{key: string, size: ?int, modified: ?string}>
     */
    public function listVideoObjects(int $maxKeys = 50): array
    {
        $client = $this->client();
        $prefix = (string) config('cloud_import.s3.prefix', '');
        if ($prefix !== '') {
            $prefix .= '/';
        }
        $videoExt = ['mp4', 'mov', 'webm', 'mkv', 'avi', 'm4v', 'mpeg', 'mpg'];
        $out = [];
        $continuationToken = null;
        $maxPages = 10;
        do {
            $params = [
                'Bucket' => (string) config('cloud_import.s3.bucket'),
                'Prefix' => $prefix,
                'MaxKeys' => 200,
            ];
            if ($continuationToken !== null) {
                $params['ContinuationToken'] = $continuationToken;
            }
            $result = $client->listObjectsV2($params);
            foreach ($result->get('Contents') ?? [] as $obj) {
                $key = (string) ($obj['Key'] ?? '');
                if ($key === '' || str_ends_with($key, '/')) {
                    continue;
                }
                $ext = strtolower(pathinfo($key, PATHINFO_EXTENSION));
                if (! in_array($ext, $videoExt, true)) {
                    continue;
                }
                $out[] = [
                    'key' => $key,
                    'size' => isset($obj['Size']) ? (int) $obj['Size'] : null,
                    'modified' => isset($obj['LastModified']) ? (string) $obj['LastModified'] : null,
                ];
                if (count($out) >= $maxKeys) {
                    return $out;
                }
            }
            $continuationToken = $result->get('IsTruncated') ? ($result->get('NextContinuationToken') ?? null) : null;
            $maxPages--;
        } while ($continuationToken !== null && $maxPages > 0);

        return $out;
    }

    public function downloadToTempFile(string $key): string
    {
        if ($key === '' || str_contains($key, '..')) {
            throw new RuntimeException('s3_bad_key');
        }
        $client = $this->client();
        $tmp = sys_get_temp_dir().'/'.uniqid('s3_', true).'.'.strtolower(pathinfo($key, PATHINFO_EXTENSION) ?: 'bin');
        $client->getObject([
            'Bucket' => (string) config('cloud_import.s3.bucket'),
            'Key' => $key,
            'SaveAs' => $tmp,
        ]);
        if (! is_readable($tmp) || filesize($tmp) === 0) {
            @unlink($tmp);
            throw new RuntimeException('s3_empty');
        }

        return $tmp;
    }

    private function client(): S3Client
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('s3_not_configured');
        }
        $cfg = [
            'version' => '2006-03-01',
            'region' => (string) config('cloud_import.s3.region', 'us-east-1'),
            'credentials' => [
                'key' => (string) config('cloud_import.s3.key'),
                'secret' => (string) config('cloud_import.s3.secret'),
            ],
        ];
        $endpoint = (string) config('cloud_import.s3.endpoint', '');
        if ($endpoint !== '') {
            $cfg['endpoint'] = $endpoint;
            $cfg['use_path_style_endpoint'] = (bool) config('cloud_import.s3.use_path_style', false);
        }

        return new S3Client($cfg);
    }
}
