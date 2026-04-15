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
