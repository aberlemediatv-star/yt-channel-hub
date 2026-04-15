<?php

namespace App\Http\Controllers\Site\Concerns;

use YtHub\Lang;
use YtHub\PublicHttp;

trait BootstrapsYtHubPublic
{
    protected function bootstrapYtHubPublic(): void
    {
        require_once base_path('src/bootstrap.php');

        PublicHttp::sendSecurityHeaders();
        Lang::init();
    }
}
