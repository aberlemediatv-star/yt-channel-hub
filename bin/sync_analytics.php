#!/usr/bin/env php
<?php

declare(strict_types=1);

require_once __DIR__.'/ythub_paths.php';
ythub_require_bootstrap();

use YtHub\Sync\AnalyticsSyncRunner;

AnalyticsSyncRunner::run();
