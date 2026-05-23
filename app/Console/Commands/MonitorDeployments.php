<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Deployment;
use App\Services\MonitoringService;

class MonitorDeployments extends Command
{
    protected $signature = 'ai:monitor-deployments';
    protected $description = 'Scan deployments and collect monitoring metrics.';

    public function handle(MonitoringService $monitor)
    {
        $deployments = Deployment::where('status', '!=', 'inactive')->get();
        foreach ($deployments as $d) {
            $this->info('Checking deployment '.$d->id.' '.$d->environment);
            $monitor->trackPerformance($d);
        }
        return 0;
    }
}
