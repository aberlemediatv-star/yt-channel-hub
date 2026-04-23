<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use YtHub\Db;
use YtHub\JobQueue;

final class JobsRecoverStalledCommand extends Command
{
    protected $signature = 'ythub:jobs-recover-stalled
                            {--seconds=1800 : Rescue jobs that have been running longer than this}';

    protected $description = 'Re-queue jobs that are stuck in "running" state.';

    public function handle(): int
    {
        require_once base_path('src/bootstrap.php');

        $seconds = max(60, (int) $this->option('seconds'));
        $n = JobQueue::recoverStalled(Db::pdo(), $seconds);
        $this->info("Re-queued {$n} stalled job(s) (threshold {$seconds}s).");

        return self::SUCCESS;
    }
}
