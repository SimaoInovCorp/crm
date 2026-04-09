<?php

namespace App\Jobs;

use App\Models\Deal;
use App\Services\AutomationRuleService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class EvaluateAutomationRulesJob implements ShouldQueue
{
    use Queueable;

    public function __construct()
    {
        //
    }

    public function handle(AutomationRuleService $automationRuleService): void
    {
        // Evaluate 'deal_idle' trigger: deals with no activity in 7+ days
        $staleDealIds = \App\Models\ActivityLog::selectRaw('loggable_id, MAX(created_at) as last_activity')
            ->where('loggable_type', Deal::class)
            ->groupBy('loggable_id')
            ->having('last_activity', '<', now()->subDays(7))
            ->pluck('loggable_id');

        Deal::whereIn('id', $staleDealIds)
            ->whereNotIn('stage', ['won', 'lost'])
            ->each(function (Deal $deal) use ($automationRuleService) {
                // Bind tenant context for each deal so HasTenant scope works
                app()->instance('current.tenant', $deal->tenant);
                $automationRuleService->evaluate('deal_idle', $deal);
            });
    }
}
