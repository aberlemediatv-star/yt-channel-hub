<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Google Drive (read-only) — eigene OAuth-Client-ID in Google Cloud Console
    | Redirect-URI muss exakt mit APP_URL + redirect_path übereinstimmen.
    |--------------------------------------------------------------------------
    */
    'gdrive' => [
        'client_id' => env('CLOUD_IMPORT_GDRIVE_CLIENT_ID', ''),
        'client_secret' => env('CLOUD_IMPORT_GDRIVE_CLIENT_SECRET', ''),
        'redirect_path' => '/staff/oauth/gdrive/callback',
    ],

    /*
    |--------------------------------------------------------------------------
    | Dropbox — App mit „Full Dropbox“ oder mindestens file.content.read
    |--------------------------------------------------------------------------
    */
    'dropbox' => [
        'app_key' => env('CLOUD_IMPORT_DROPBOX_APP_KEY', ''),
        'app_secret' => env('CLOUD_IMPORT_DROPBOX_APP_SECRET', ''),
        'redirect_path' => '/staff/oauth/dropbox/callback',
    ],

    /*
    |--------------------------------------------------------------------------
    | S3-kompatibel (AWS, MinIO, …) — optional, nur wenn bucket gesetzt
    |--------------------------------------------------------------------------
    */
    's3' => [
        'enabled' => env('CLOUD_IMPORT_S3_ENABLED', false),
        'key' => env('CLOUD_IMPORT_S3_KEY', env('AWS_ACCESS_KEY_ID', '')),
        'secret' => env('CLOUD_IMPORT_S3_SECRET', env('AWS_SECRET_ACCESS_KEY', '')),
        'region' => env('CLOUD_IMPORT_S3_REGION', env('AWS_DEFAULT_REGION', 'us-east-1')),
        'bucket' => env('CLOUD_IMPORT_S3_BUCKET', ''),
        'prefix' => trim((string) env('CLOUD_IMPORT_S3_PREFIX', ''), '/'),
        'endpoint' => env('CLOUD_IMPORT_S3_ENDPOINT', ''),
        'use_path_style' => env('CLOUD_IMPORT_S3_USE_PATH_STYLE', false),
    ],
];
