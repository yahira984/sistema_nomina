<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;

class QueueHeartbeatJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 30;

    public function __construct()
    {
        $this->onQueue('default');
    }

    public function handle(): void
    {
        Cache::put('system:queue-heartbeat', now()->toISOString(), now()->addMinutes(10));
    }
}
