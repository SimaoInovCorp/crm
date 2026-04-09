<?php

namespace App\Jobs;

use App\Models\FollowUpAutomation;
use App\Services\FollowUpService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class FollowUpEmailJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $followUpAutomationId)
    {
        //
    }

    public function handle(FollowUpService $followUpService): void
    {
        $followUp = FollowUpAutomation::with(['deal', 'emailTemplate'])->find($this->followUpAutomationId);

        if (! $followUp) {
            return;
        }

        $followUpService->sendNext($followUp);
    }
}
