<?php

use App\Jobs\DailyDealAnalysisJob;
use App\Jobs\EvaluateAutomationRulesJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Daily automation rules evaluation
Schedule::job(new EvaluateAutomationRulesJob)->daily();

// Daily AI sales agent deal analysis
Schedule::job(new DailyDealAnalysisJob)->dailyAt('07:00');
