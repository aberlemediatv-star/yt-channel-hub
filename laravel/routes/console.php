<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('queue:work database --max-time=300 --tries=3 --stop-when-empty')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();

Schedule::command('queue:prune-failed --hours=168')
    ->daily();

Schedule::command('queue:prune-batches --hours=168')
    ->daily();

// Social posts: scheduler + retry tick.
Schedule::command('social:run-due --limit=20')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();

// Re-queue jobs that have been stuck 'running' for over 30 minutes.
Schedule::command('ythub:jobs-recover-stalled --seconds=1800')
    ->everyTenMinutes()
    ->withoutOverlapping();
