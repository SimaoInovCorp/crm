<?php

use App\Http\Controllers\Api\ActivityLogController;
use App\Http\Controllers\Api\AiSuggestionController;
use App\Http\Controllers\Api\AutomationRuleController;
use App\Http\Controllers\Api\CalendarEventController;
use App\Http\Controllers\Api\CsvImportController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DealController;
use App\Http\Controllers\Api\DealStageController;
use App\Http\Controllers\Api\DealTimelineController;
use App\Http\Controllers\Api\EmailTemplateController;
use App\Http\Controllers\Api\EntityController;
use App\Http\Controllers\Api\FollowUpController;
use App\Http\Controllers\Api\LeadFormController;
use App\Http\Controllers\Api\PersonController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProductStatisticsController;
use App\Http\Controllers\Api\ProposalController;
use App\Http\Controllers\Api\PublicLeadFormController;
use App\Http\Controllers\Api\SmartChatController;
use App\Http\Controllers\Api\TenantController;
use App\Http\Controllers\Api\TenantSwitchController;
use App\Http\Controllers\Api\WebhookController;
use Illuminate\Support\Facades\Route;

// Public endpoints — no auth required
Route::middleware(['throttle:10,1'])->group(function () {
    Route::post('public/forms/{token}', [PublicLeadFormController::class, 'submit'])->name('public.forms.submit');
});

Route::middleware(['auth'])->group(function () {
    // Tenants -- no tenant middleware here, user selects/creates tenants freely
    Route::apiResource('tenants', TenantController::class)->except(['destroy']);
    Route::post('switch-tenant', TenantSwitchController::class)->name('tenants.switch');
});

Route::middleware(['auth', 'tenant'])->group(function () {
    // Dashboard statistics
    Route::get('dashboard', DashboardController::class)->name('dashboard.stats');

    // Entities
    Route::get('entities/export', [EntityController::class, 'export'])->name('entities.export');
    Route::apiResource('entities', EntityController::class);
    Route::post('entities/{entity}/email', [EntityController::class, 'sendEmail'])->name('entities.email');

    // People
    Route::get('people/export', [PersonController::class, 'export'])->name('people.export');
    Route::apiResource('people', PersonController::class);
    Route::post('people/{person}/email', [PersonController::class, 'sendEmail'])->name('people.email');

    // Deals
    Route::get('deals/export', [DealController::class, 'export'])->name('deals.export');
    Route::apiResource('deals', DealController::class);
    Route::patch('deals/{deal}/stage', DealStageController::class)->name('deals.stage');
    Route::get('deals/{deal}/timeline', [DealTimelineController::class, 'index'])->name('deals.timeline');
    Route::get('deals/{deal}/timeline/export', [DealTimelineController::class, 'export'])->name('deals.timeline.export');
    Route::post('deals/{deal}/proposal', [ProposalController::class, 'store'])->name('deals.proposal');
    Route::get('deals/{deal}/follow-up', [FollowUpController::class, 'show'])->name('deals.follow-up.show');
    Route::post('deals/{deal}/follow-up', [FollowUpController::class, 'start'])->name('deals.follow-up.start');
    Route::post('deals/{deal}/follow-up/cancel', [FollowUpController::class, 'cancel'])->name('deals.follow-up.cancel');
    Route::post('deals/{deal}/follow-up/send-now', [FollowUpController::class, 'sendNow'])->name('deals.follow-up.send-now');

    // Calendar Events
    Route::apiResource('calendar-events', CalendarEventController::class);
    Route::post('calendar-events/{calendarEvent}/send-invoice', [CalendarEventController::class, 'sendInvoice'])->name('calendar-events.send-invoice');

    // Activity Logs
    Route::get('activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');
    Route::post('activity-logs', [ActivityLogController::class, 'store'])->name('activity-logs.store');

    // Email Templates
    Route::apiResource('email-templates', EmailTemplateController::class);

    // Automation Rules
    Route::apiResource('automation-rules', AutomationRuleController::class)->except(['show']);

    // Lead Forms
    Route::apiResource('lead-forms', LeadFormController::class);
    Route::get('lead-forms/{leadForm}/submissions', [LeadFormController::class, 'submissions'])->name('lead-forms.submissions');

    // Product Statistics
    Route::get('products/statistics', [ProductStatisticsController::class, 'index'])->name('products.statistics');
    Route::get('products/{product}/drill-down', [ProductStatisticsController::class, 'drillDown'])->name('products.drill-down');

    // Products CRUD
    Route::apiResource('products', ProductController::class)->except(['show']);

    // Smart Chat (SSE streaming)
    Route::post('chat', SmartChatController::class)->name('chat');

    // AI Suggestions
    Route::get('ai-suggestions', [AiSuggestionController::class, 'index'])->name('ai-suggestions.index');
    Route::post('ai-suggestions/{aiSuggestion}/accept', [AiSuggestionController::class, 'accept'])->name('ai-suggestions.accept');
    Route::post('ai-suggestions/{aiSuggestion}/dismiss', [AiSuggestionController::class, 'dismiss'])->name('ai-suggestions.dismiss');
    Route::post('ai-suggestions/{aiSuggestion}/postpone', [AiSuggestionController::class, 'postpone'])->name('ai-suggestions.postpone');

    // Webhooks
    Route::apiResource('webhooks', WebhookController::class)->except(['show']);

    // CSV Import
    Route::post('import/{type}', [CsvImportController::class, 'store'])->name('import.store')
        ->where('type', 'entities|people|deals');
});
