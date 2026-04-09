<?php

namespace App\Jobs;

use App\Models\Tenant;
use App\Services\AiSalesAgentService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class DailyDealAnalysisJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function handle(AiSalesAgentService $service): void
    {
        Tenant::all()->each(function (Tenant $tenant) use ($service) {
            $service->analyzeDeals($tenant);
        });
    }
}
